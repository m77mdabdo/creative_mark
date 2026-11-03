<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consultation bookings from the public site, the team's email replies, and who is notified of new bookings.
 * Personal data is kept to what the consultation needs; the IP address is stored only as a keyed hash.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_requests', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 20)->unique();
            $t->string('status', 16)->default('new'); // App\Enums\ConsultationStatus
            $t->string('name', 120);
            $t->string('email', 190);
            $t->string('phone', 32);
            $t->string('company', 160)->nullable();
            $t->string('country', 2)->nullable(); // where the client is based
            $t->string('service', 64)->nullable(); // service slug
            $t->string('region', 32)->nullable(); // region slug
            $t->string('stage', 24)->nullable(); // exploring | ready | operating
            $t->string('meeting_mode', 16); // video | phone | office_riyadh | office_cairo
            $t->date('preferred_date');
            $t->string('preferred_slot', 5); // HH:MM, Riyadh time
            $t->text('message')->nullable();
            $t->string('locale', 8);
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->text('internal_notes')->nullable();
            $t->timestamp('consent_at');
            $t->string('ip_hash', 64)->nullable();
            $t->string('user_agent', 255)->nullable();
            $t->timestamp('notified_at')->nullable(); // team emails sent
            $t->timestamp('confirmed_at')->nullable(); // confirmation email sent to the client
            $t->string('mail_error', 500)->nullable();
            $t->timestamps();
            $t->index(['status', 'created_at']);
            $t->index(['preferred_date', 'preferred_slot']);
        });

        Schema::create('consultation_replies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('consultation_request_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('subject', 200);
            $t->text('body');
            $t->timestamp('sent_at')->nullable();
            $t->string('error', 500)->nullable();
            $t->timestamps();
        });

        Schema::create('notification_recipients', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('email', 190)->unique();
            $t->boolean('active')->default(true);
            $t->timestamp('last_notified_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_recipients');
        Schema::dropIfExists('consultation_replies');
        Schema::dropIfExists('consultation_requests');
    }
};

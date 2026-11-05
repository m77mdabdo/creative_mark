<?php

namespace App\Consultation;

use App\Models\ConsultationRequest;
use App\Models\Country;
use App\Models\Region;
use App\Models\Service;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use ZipArchive;

/**
 * Consultation bookings as an Excel workbook (.xlsx) in the dashboard's language. Every value is written as plain text,
 * so a client's message can never run as an Excel formula, and Arabic opens correctly with no import step.
 */
final class BookingExport
{
    /** @return list<string> */
    private static function headings(): array
    {
        return [__('Reference'), __('Received'), __('Status'), __('Name'), __('Email'), __('Phone'), __('Company'), __('Based in'), __('Service'), __('Region'), __('Stage'),
            __('Meeting'), __('Date'), __('Time'), __('Language'), __('Assigned to'), __('Message'), __('Internal notes')];
    }

    /** Writes the workbook to a temporary file and returns its path and the number of bookings. @return array{0: string, 1: int} */
    public static function toFile(Builder $bookings): array
    {
        $locale = app()->getLocale();
        $tz = config('consultation.timezone');
        $services = Service::query()->with('translations')->get()->mapWithKeys(fn ($s) => [$s->slug => $s->t('title', $locale)]);
        $regions = Region::query()->with('translations')->get()->mapWithKeys(fn ($r) => [$r->slug => $r->t('name', $locale)]);
        $countries = Country::query()->with('translations')->get()->mapWithKeys(fn ($c) => [$c->code => $c->t('name', $locale)]);

        $rows = [self::headings()];
        $count = 0;
        foreach ($bookings->with('assignee')->orderByDesc('created_at')->cursor() as $b) {
            /** @var ConsultationRequest $b */
            $rows[] = [
                $b->reference, $b->created_at->timezone($tz)->format('Y-m-d H:i'), $b->status->label(), $b->name, $b->email, $b->phone, $b->company,
                $b->country === 'xx' ? __('Other') : ($countries[$b->country] ?? $b->country), $services[$b->service] ?? $b->service, $regions[$b->region] ?? $b->region,
                $b->stage ? MailStrings::get($locale, "stage_{$b->stage}") : null, MailStrings::get($locale, "mode_{$b->meeting_mode}"),
                $b->preferred_date->format('Y-m-d'), $b->preferred_slot, Locales::ALL[$b->locale]['native'] ?? $b->locale, $b->assignee?->name, $b->message, $b->internal_notes,
            ];
            $count++;
        }

        return [self::write($rows, Locales::dir($locale) === 'rtl'), $count];
    }

    /** @param list<list<string|null>> $rows */
    private static function write(array $rows, bool $rtl): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cm-bookings');
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Cannot create the workbook.');
        }

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"'.($rtl ? ' rightToLeft="1"' : '').'><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols><col min="1" max="18" width="22" customWidth="1"/></cols><sheetData>';
        foreach ($rows as $r => $row) {
            $sheet .= '<row r="'.($r + 1).'">';
            foreach (array_values($row) as $c => $value) {
                $sheet .= '<c r="'.self::cell($c, $r + 1).'" t="inlineStr"'.($r === 0 ? ' s="1"' : '').'><is><t xml:space="preserve">'.self::xml((string) $value).'</t></is></c>';
            }
            $sheet .= '</row>';
        }
        $sheet .= '</sheetData></worksheet>';

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Consultations" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Arial"/></font><font><b/><sz val="11"/><name val="Arial"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        return $path;
    }

    private static function cell(int $column, int $row): string
    {
        $name = '';
        for ($n = $column; $n >= 0; $n = intdiv($n, 26) - 1) {
            $name = chr(65 + $n % 26).$name;
        }

        return $name.$row;
    }

    /** Escapes text and removes characters XML cannot hold. */
    private static function xml(string $value): string
    {
        return htmlspecialchars((string) preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}

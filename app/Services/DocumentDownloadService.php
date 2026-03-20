<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\Tcpdf\Fpdi;
use App\Enums\Actions;
use App\Models\DocAssignmentAction;
use App\Models\Status;
use App\Models\User;

class DocumentDownloadService
{
    /**
     * A4 Portrait printable width = 210mm - 15mm (left) - 15mm (right) = 180mm
     * Column widths must sum to exactly 180mm.
     *
     * Signatories table columns (180mm total):
     *   Name: 45 | Role: 30 | Office: 20 | Department: 25 | Designation: 25 | Action: 15 | Date & Time: 20
     *
     * Action Logs table columns (180mm total):
     *   Name: 45 | Role: 30 | Office: 20 | Department: 25 | Designation: 25 | Action: 15 | Date & Time: 20
     */

    // A4 Portrait: 210mm - 15mm left - 15mm right = 180mm printable width
    // All COL_* values must sum to 180.
    private const COL_NAME        = 30;
    private const COL_ROLE        = 18;
    private const COL_OFFICE      = 32;
    private const COL_DEPARTMENT  = 25;
    private const COL_DESIGNATION = 25;
    private const COL_ACTION      = 28;
    private const COL_DATETIME    = 22;

    private const LINE_HEIGHT     = 5;   // height of each text line inside a MultiCell
    private const HEADER_HEIGHT   = 10;
    private const FONT_HEADER     = 8;
    private const FONT_ROW        = 7;
    private const LEFT_MARGIN     = 15;

    /**
     * Build signatories from DocAssignmentAction collection.
     * Only include SIGNED actions.
     */
    public function buildSignatoriesFromActions($actions): array
    {
        $collection = is_array($actions) ? collect($actions) : $actions;

        return $collection
            ->filter(fn($a) => $a->action === Actions::SIGNED->value)
            ->map(fn(DocAssignmentAction $a) => [
                'name'        => optional($a->user)->first_name . ' ' . optional($a->user)->last_name ?? 'N/A',
                'role'        => optional($a->user)->role ?? 'N/A',
                'office'      => optional($a->user)->office ?? 'N/A',
                'department'  => optional($a->user)->department ?? 'N/A',
                'designation' => optional($a->user)->designation ?? 'N/A',
                'action'      => $a->action,
                'datetime'    => optional($a->created_at)->format('Y-m-d H:i:s') ?? now()->toDateTimeString(),
            ])
            ->sortBy('datetime')
            ->values()
            ->all();
    }

    /**
     * Build all action logs from DocAssignmentAction collection.
     * Include ALL actions for audit trail.
     */
    public function buildActionLogsFromActions($actions): array
    {
        $collection = is_array($actions) ? collect($actions) : $actions;

        return $collection
            ->map(fn(DocAssignmentAction $a) => [
                'name'        => optional($a->user)->first_name . ' ' . optional($a->user)->last_name ?? 'N/A',
                'role'        => optional($a->user)->role ?? 'N/A',
                'office'      => optional($a->user)->office ?? 'N/A',
                'department'  => optional($a->user)->department ?? 'N/A',
                'designation' => optional($a->user)->designation ?? 'N/A',
                'action'      => $a->action,
                'datetime'    => optional($a->created_at)->format('Y-m-d H:i:s') ?? now()->toDateTimeString(),
            ])
            ->sortBy('datetime')
            ->values()
            ->all();
    }

    /**
     * Generate PDF from Cloudinary URL and append SIGNED and ACTION LOG pages.
     */
    public function makeSignedPdfFromUrl(
        string $cloudinaryUrl,
        array $signatories,
        array $actionLogs = [],
        ?string $watermarkText = null,
        ?array $options = null,
        ?User $user = null
    ): string {
        $options = array_merge([
            'include_footer' => true,
            'title'          => 'DIGITAL SIGNATORIES',
        ], (array) $options);

        $response = Http::timeout(30)->get($cloudinaryUrl);
        if (!$response->ok() || empty($response->body())) {
            throw new \RuntimeException('Failed to fetch PDF from URL: ' . $cloudinaryUrl);
        }
        $pdfBytes = $response->body();

        $pdf = new Fpdi();
        $pdf->SetCreator('DocuSphere DTS');
        $pdf->SetAuthor('DocuSphere');
        $pdf->SetTitle($options['title']);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(self::LEFT_MARGIN, 15, self::LEFT_MARGIN);

        $reader     = StreamReader::createByString($pdfBytes);
        $pageCount  = $pdf->setSourceFile($reader);

        for ($i = 1; $i <= $pageCount; $i++) {
            $tplId = $pdf->importPage($i);
            $size  = $pdf->getTemplateSize($tplId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId);
        }

        $this->appendSignatoriesPage($pdf, $signatories, $watermarkText, $options, $user);

        if (!empty($actionLogs)) {
            $this->appendActionLogsPage($pdf, $actionLogs, $watermarkText, $options, $user);
        }

        return $pdf->Output('', 'S');
    }

    /**
     * Append the Digital Signatories page (SIGNED actions only).
     */
    private function appendSignatoriesPage(Fpdi $pdf, array $signatories, ?string $watermarkText, array $options, User $user): void
    {
        $pdf->AddPage('P', 'A4');
        $pdf->SetMargins(self::LEFT_MARGIN, 15, self::LEFT_MARGIN);

        $pageWidth  = $pdf->getPageWidth();
        $pageHeight = $pdf->getPageHeight();

        $this->addWatermark($pdf, $watermarkText, $pageWidth, $pageHeight);

        $pdf->SetY(20);

        // Page title
        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->Cell(0, 12, $options['title'], 0, 1, 'C');
        $pdf->SetLineWidth(0.5);
        $pdf->Line(self::LEFT_MARGIN, $pdf->GetY(), $pageWidth - self::LEFT_MARGIN, $pdf->GetY());
        $pdf->SetLineWidth(0.2);
        $pdf->Ln(6);

        // Description
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(
            0, 5,
            'This certification page provides a complete record of all authorized signatories who have reviewed and signed this document. The original document content remains unaltered and legally binding.',
            0, 'L', false
        );
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(6);

        // Table header
        $this->drawTableHeader($pdf, 'Signatory Name');

        // Table rows
        $pdf->SetFont('helvetica', '', self::FONT_ROW);
        foreach ($signatories as $i => $sig) {
            $this->drawTableRow($pdf, [
                $sig['name'],
                $sig['role'],
                $sig['office'],
                $sig['department'],
                $sig['designation'],
                $sig['action'],
                $sig['datetime'],
            ]);
        }

        if (empty($signatories)) {
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Cell(0, self::LINE_HEIGHT * 2, 'No signatories found.', 1, 1, 'C');
        }

        if ($options['include_footer']) {
            $this->addFooter($pdf, $pageWidth, $pageHeight, $user);
        }
    }

    /**
     * Append the Action Audit Trail page (ALL actions).
     */
    private function appendActionLogsPage(Fpdi $pdf, array $actionLogs, ?string $watermarkText, array $options, User $user): void
    {
        $pdf->AddPage('P', 'A4');
        $pdf->SetMargins(self::LEFT_MARGIN, 15, self::LEFT_MARGIN);

        $pageWidth  = $pdf->getPageWidth();
        $pageHeight = $pdf->getPageHeight();

        $this->addWatermark($pdf, $watermarkText, $pageWidth, $pageHeight);

        $pdf->SetY(20);

        // Page title
        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->Cell(0, 12, 'ACTION AUDIT TRAIL', 0, 1, 'C');
        $pdf->SetLineWidth(0.5);
        $pdf->Line(self::LEFT_MARGIN, $pdf->GetY(), $pageWidth - self::LEFT_MARGIN, $pdf->GetY());
        $pdf->SetLineWidth(0.2);
        $pdf->Ln(6);

        // Description
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(
            0, 5,
            'This audit trail provides a comprehensive chronological record of all actions performed on this document, including views, downloads, signatures, and other interactions. This log serves as an official record for compliance and verification purposes.',
            0, 'L', false
        );
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(6);

        // Table header
        $this->drawTableHeader($pdf, 'User Name');

        // Table rows
        $pdf->SetFont('helvetica', '', self::FONT_ROW);
        foreach ($actionLogs as $i => $log) {
            $this->drawTableRow($pdf, [
                $log['name'],
                $log['role'],
                $log['office'],
                $log['department'],
                $log['designation'],
                $log['action'],
                $log['datetime'],
            ]);
        }

        if (empty($actionLogs)) {
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Cell(0, self::LINE_HEIGHT * 2, 'No action logs found.', 1, 1, 'C');
        }

        if ($options['include_footer']) {
            $this->addFooter($pdf, $pageWidth, $pageHeight, $user);
        }
    }

    /**
     * Draw the shared table header row.
     * Total = 45+30+20+25+25+15+20 = 180mm (matches A4 portrait printable width)
     */
    private function drawTableHeader(Fpdi $pdf, string $nameLabel): void
    {
        $pdf->SetLineWidth(0.2);
        $pdf->SetFillColor(240, 240, 240);   // gray
        $pdf->SetFont('helvetica', 'B', self::FONT_HEADER);

        $pdf->Cell(self::COL_NAME,        self::HEADER_HEIGHT, $nameLabel,     1, 0, 'C', true);
        $pdf->Cell(self::COL_ROLE,        self::HEADER_HEIGHT, 'Role', 1, 0, 'C', true);
        $pdf->Cell(self::COL_OFFICE,      self::HEADER_HEIGHT, 'Office',        1, 0, 'C', true);
        $pdf->Cell(self::COL_DEPARTMENT,  self::HEADER_HEIGHT, 'Department',    1, 0, 'C', true);
        $pdf->Cell(self::COL_DESIGNATION, self::HEADER_HEIGHT, 'Designation',   1, 0, 'C', true);
        $pdf->Cell(self::COL_ACTION,      self::HEADER_HEIGHT, 'Action',        1, 0, 'C', true);
        $pdf->Cell(self::COL_DATETIME,    self::HEADER_HEIGHT, 'Date & Time',   1, 1, 'C', true);

    }

    /**
     * Draw one table row using MultiCell so text wraps instead of overflowing.
     *
     * Strategy:
     *  1. For each column, use GetStringWidth() to calculate how many lines the
     *     text needs at the given column width.
     *  2. Find the tallest column — that becomes the row height.
     *  3. Draw each column at the saved X position using MultiCell with that
     *     unified height, then restore Y so the next column starts at the same Y.
     *  4. Draw a bounding border rectangle over each cell manually so all borders
     *     are the same height regardless of content.
     */
    private function drawTableRow(Fpdi $pdf, array $row): void
    {
        $cols = [
            ['width' => self::COL_NAME,        'align' => 'L', 'text' => $row[0]],
            ['width' => self::COL_ROLE,        'align' => 'L', 'text' => $row[1]],
            ['width' => self::COL_OFFICE,      'align' => 'C', 'text' => $row[2]],
            ['width' => self::COL_DEPARTMENT,  'align' => 'C', 'text' => $row[3]],
            ['width' => self::COL_DESIGNATION, 'align' => 'C', 'text' => $row[4]],
            ['width' => self::COL_ACTION,      'align' => 'C', 'text' => $row[5]],
            ['width' => self::COL_DATETIME,    'align' => 'C', 'text' => $row[6]],
        ];

        $lineH   = self::LINE_HEIGHT;
        $padding = 2; // mm padding inside cell

        // Calculate the number of lines each column needs
        $maxLines = 1;
        foreach ($cols as &$col) {
            $usableWidth = $col['width'] - ($padding * 2);
            $words       = explode(' ', $col['text']);
            $lines       = 1;
            $lineText    = '';

            foreach ($words as $word) {
                $test = $lineText === '' ? $word : $lineText . ' ' . $word;
                if ($pdf->GetStringWidth($test) > $usableWidth && $lineText !== '') {
                    $lines++;
                    $lineText = $word;
                } else {
                    $lineText = $test;
                }
            }

            $col['lines'] = $lines;
            $maxLines     = max($maxLines, $lines);
        }
        unset($col);

        $rowHeight = $maxLines * $lineH + ($padding * 2);
        $startY    = $pdf->GetY();
        $startX    = self::LEFT_MARGIN;

        // Check if row will overflow page; if so, add new page
        $pageHeight  = $pdf->getPageHeight();
        $footerSpace = 30;
        if ($startY + $rowHeight > $pageHeight - $footerSpace) {
            $pdf->AddPage('P', 'A4');
            $pdf->SetMargins(self::LEFT_MARGIN, 15, self::LEFT_MARGIN);
            $startY = $pdf->GetY();
        }

        
        // Draw each cell
        $curX = $startX;
        foreach ($cols as $col) {
            $pdf->SetXY($curX + $padding, $startY + $padding);

            // Draw text with MultiCell (no border — we draw border manually below)
            $pdf->MultiCell(
                $col['width'] - ($padding * 2),
                $lineH,
                $col['text'],
                0,
                $col['align'],
                false
            );

            // Draw border rect on top
            $pdf->SetLineWidth(0.2);
            $pdf->Rect($curX, $startY, $col['width'], $rowHeight, 'D');

            $curX += $col['width'];
        }

        // Advance Y past this row
        $pdf->SetXY(self::LEFT_MARGIN, $startY + $rowHeight);
    }

    /**
     * Add watermark to the current page.
     */
    private function addWatermark(Fpdi $pdf, ?string $watermarkText, float $pageWidth, float $pageHeight): void
    {
        if (!$watermarkText) {
            return;
        }

        if (method_exists($pdf, 'setAlpha')) {
            $pdf->setAlpha(0.08);
        }
        $pdf->SetFont('helvetica', 'B', 50);
        $pdf->SetTextColor(200, 200, 200);
        $pdf->StartTransform();
        $pdf->Rotate(45, $pageWidth / 2, $pageHeight / 2);
        $pdf->Text($pageWidth / 2 - 60, $pageHeight / 2 - 20, $watermarkText);
        $pdf->StopTransform();
        if (method_exists($pdf, 'setAlpha')) {
            $pdf->setAlpha(1);
        }
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Add footer to the current page.
     */
    private function addFooter(Fpdi $pdf, float $pageWidth, float $pageHeight, User $user): void
    {
        $pdf->SetY($pageHeight - 25);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(self::LEFT_MARGIN, $pdf->GetY(), $pageWidth - self::LEFT_MARGIN, $pdf->GetY());
        $pdf->Ln(3);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(100, 100, 100);

        $footerText = 'Digitally generated by DocuSphere DTS | '
            . now()->format('F d, Y \a\t H:i:s T')
            . ' | Downloaded by: ' . $user->first_name . ' ' . $user->last_name
            . ' (' . $user->role . ')';

        $pdf->Cell(0, 5, $footerText, 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Directly send the PDF to the browser as a download.
     */
    public function downloadSignedPdfResponse(
        string $cloudinaryUrl,
        array $signatories,
        array $actionLogs = [],
        ?string $watermarkText = null,
        string $filename = 'signed_document.pdf',
        User $user = null
    ) {
        $pdfBinary = $this->makeSignedPdfFromUrl($cloudinaryUrl, $signatories, $actionLogs, $watermarkText, user: $user);

        return response($pdfBinary, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . addslashes($filename) . '"',
            'Content-Length'      => strlen($pdfBinary),
        ]);
    }


    /**
     * Monthly Report column widths (180mm total):
     * Tracking No: 25 | Title: 40 | Category: 22 | Request Type: 22 | Originating Office: 30 | Status: 20 | Due Date: 21
     */
    private const COL_TRACKING      = 35;
<<<<<<< HEAD
    private const COL_TITLE         = 0;
=======
    private const COL_TITLE         = 25;
>>>>>>> 6aede08f1de850becef491562800e1a20dded3f7
    private const COL_CATEGORY      = 22;
    private const COL_REQUEST_TYPE  = 27;
    private const COL_ORIG_OFFICE   = 30;
    private const COL_STATUS        = 20;
    private const COL_DUE_DATE      = 21;

   /**
     * Generate a monthly report PDF and return binary string.
     */
    public function makeMonthlyReportPdf(
        \Illuminate\Support\Collection $documents,
        ?User $user = null,
        ?array $options = null
    ): string {
        $options = array_merge([
            'include_footer' => true,
            'title'          => 'MONTHLY DOCUMENT REPORT',
            'month_label'    => now()->format('F Y'),
            'watermark'      => 'Docusphere DTS',
        ], (array) $options);

        $pdf = new Fpdi();
        $pdf->SetCreator('DocuSphere DTS');
        $pdf->SetAuthor('DocuSphere');
        $pdf->SetTitle($options['title']);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(self::LEFT_MARGIN, 15, self::LEFT_MARGIN);

        $this->appendMonthlyReportPage($pdf, $documents, $options, $user);

        return $pdf->Output('', 'S');
    }

    /**
     * Append monthly report page — DepEd Makati formal style.
     * Consistent with appendSignatoriesPage / appendActionLogsPage.
     */
    private function appendMonthlyReportPage(
        Fpdi $pdf,
        \Illuminate\Support\Collection $documents,
        array $options,
        ?User $user
    ): void {
        $pdf->AddPage('P', 'A4');
        $pdf->SetMargins(self::LEFT_MARGIN, 15, self::LEFT_MARGIN);

        $pageWidth  = $pdf->getPageWidth();
        $pageHeight = $pdf->getPageHeight();

        $this->addWatermark($pdf, $options['watermark'] ?? 'OFFICIAL RECORD', $pageWidth, $pageHeight);

        $pdf->SetY(20);

        // Page title
        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 12, $options['title'], 0, 1, 'C');
        $pdf->SetLineWidth(0.5);
        $pdf->Line(self::LEFT_MARGIN, $pdf->GetY(), $pageWidth - self::LEFT_MARGIN, $pdf->GetY());
        $pdf->SetLineWidth(0.2);
        $pdf->Ln(6);

        // Meta block: period, generated at, downloaded by
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(60, 60, 60);

        $pdf->Cell(30, 5, 'User:', 0, 0, 'L');
        $pdf->setFont('helvetica', 'B', 8);
        $pdf->Cell(0, 5, $user->first_name . ' ' . $user->last_name, 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(30, 5, 'Reporting Period:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(0, 5, $options['month_label'], 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(30, 5, 'Generated:', 0, 0, 'L');
        $pdf->Cell(0, 5, now()->format('F d, Y \a\t H:i:s T'), 0, 1, 'L');

        if ($user) {
            $pdf->Cell(30, 5, 'Downloaded by:', 0, 0, 'L');
            $pdf->Cell(0, 5, $user->first_name . ' ' . $user->last_name . '  |  ' . ($user->role ?? 'N/A'), 0, 1, 'L');
        }

        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(30, 5, 'Total Documents:', 0, 0, 'L');
        $pdf->Cell(0, 5, (string) $documents->count(), 0, 1, 'L');

        $pdf->Ln(3);
        $pdf->SetLineWidth(0.2);
        $pdf->Line(self::LEFT_MARGIN, $pdf->GetY(), $pageWidth - self::LEFT_MARGIN, $pdf->GetY());
        $pdf->Ln(5);

        // Description
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(
            0, 5,
            'This report provides a summary of all documents handled during the reporting period. ' .
            'All information is system-generated and reflects official records from DocuSphere DTS.',
            0, 'L', false
        );
        $pdf->Ln(5);

<<<<<<< HEAD
        // Separator before table
        $pdf->SetY($boxY + 32);
        $pdf->SetX(self::LEFT_MARGIN);
        $pdf->SetDrawColor(200, 210, 225);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(self::LEFT_MARGIN, $pdf->GetY(), $pageWidth - self::LEFT_MARGIN, $pdf->GetY());
        $pdf->Ln(4);

        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        $pdf->SetTextColor(0, 0, 0);

        // ── Table ─────────────────────────────────────────────────────────────────
=======
        // Table
>>>>>>> 6aede08f1de850becef491562800e1a20dded3f7
        $this->drawMonthlyReportTableHeader($pdf);

        $pdf->SetFont('helvetica', '', self::FONT_ROW);
        foreach ($documents as $doc) {
            $this->drawMonthlyReportTableRow($pdf, $doc, $pageWidth, $pageHeight);
        }

        if ($documents->isEmpty()) {
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Cell(0, self::LINE_HEIGHT * 2, 'No documents found for this period.', 1, 1, 'C');
        }

        if ($options['include_footer'] && $user) {
            $this->addFooter($pdf, $pageWidth, $pageHeight, $user);
        }
    }


    /**
     * Draw the monthly report table header.
     */
    private function drawMonthlyReportTableHeader(Fpdi $pdf): void
    {
        $pdf->SetLineWidth(0.2);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('helvetica', 'B', self::FONT_HEADER);

        $pdf->Cell(self::COL_TRACKING,     self::HEADER_HEIGHT, 'Tracking No',       1, 0, 'C', true);
        $pdf->Cell(self::COL_TITLE,        self::HEADER_HEIGHT, 'Title',              1, 0, 'C', true);
        $pdf->Cell(self::COL_CATEGORY,     self::HEADER_HEIGHT, 'Category',           1, 0, 'C', true);
        $pdf->Cell(self::COL_REQUEST_TYPE, self::HEADER_HEIGHT, 'Request Type',       1, 0, 'C', true);
        $pdf->Cell(self::COL_ORIG_OFFICE,  self::HEADER_HEIGHT, 'Originating Office', 1, 0, 'C', true);
        $pdf->Cell(self::COL_STATUS,       self::HEADER_HEIGHT, 'Status',             1, 0, 'C', true);
        $pdf->Cell(self::COL_DUE_DATE,     self::HEADER_HEIGHT, 'Due Date',           1, 1, 'C', true);
    }

    /**
     * Draw one monthly report table row with proper multi-line and page-break handling.
     */
    private function drawMonthlyReportTableRow(Fpdi $pdf, $doc, float $pageWidth, float $pageHeight): void
    {
        // ── Status fix: status_id is an integer FK, pass it directly to Status::label()
        $statusLabel  = Status::label((int) $doc->status_id);
        $dueDateLabel = $doc->due_date
            ? \Carbon\Carbon::parse($doc->due_date)->format('Y-m-d')
            : 'N/A';

        $cols = [
            ['width' => self::COL_TRACKING,     'align' => 'C', 'text' => $doc->tracking_no        ?? 'N/A'],
            ['width' => self::COL_TITLE,        'align' => 'L', 'text' => $doc->title               ?? 'N/A'],
            ['width' => self::COL_CATEGORY,     'align' => 'C', 'text' => $doc->category            ?? 'N/A'],
            ['width' => self::COL_REQUEST_TYPE, 'align' => 'C', 'text' => $doc->request_type        ?? 'N/A'],
            ['width' => self::COL_ORIG_OFFICE,  'align' => 'L', 'text' => $doc->originating_office  ?? 'N/A'],
            ['width' => self::COL_STATUS,       'align' => 'C', 'text' => $statusLabel],
            ['width' => self::COL_DUE_DATE,     'align' => 'C', 'text' => $dueDateLabel],
        ];

        $lineH   = self::LINE_HEIGHT;
        $padding = 2;

        $maxLines = 1;
        foreach ($cols as &$col) {
            $usableWidth = $col['width'] - ($padding * 2);
            $words       = explode(' ', $col['text']);
            $lines       = 1;
            $lineText    = '';

            foreach ($words as $word) {
                $test = $lineText === '' ? $word : $lineText . ' ' . $word;
                if ($pdf->GetStringWidth($test) > $usableWidth && $lineText !== '') {
                    $lines++;
                    $lineText = $word;
                } else {
                    $lineText = $test;
                }
            }

            $col['lines'] = $lines;
            $maxLines     = max($maxLines, $lines);
        }
        unset($col);

        $rowHeight   = $maxLines * $lineH + ($padding * 2);
        $footerSpace = 30;
        $startY      = $pdf->GetY();

        if ($startY + $rowHeight > $pageHeight - $footerSpace) {
            $pdf->AddPage('P', 'A4');
            $pdf->SetMargins(self::LEFT_MARGIN, 15, self::LEFT_MARGIN);
            // Watermark on continuation pages too
            $this->addWatermark($pdf, 'Docusphere DTS', $pageWidth, $pageHeight);
            $this->drawMonthlyReportTableHeader($pdf);
            $startY = $pdf->GetY();
        }

        $curX = self::LEFT_MARGIN;
        foreach ($cols as $col) {
            $pdf->SetXY($curX + $padding, $startY + $padding);
            $pdf->MultiCell(
                $col['width'] - ($padding * 2),
                $lineH,
                $col['text'],
                0,
                $col['align'],
                false
            );
            $pdf->SetLineWidth(0.2);
            $pdf->Rect($curX, $startY, $col['width'], $rowHeight, 'D');
            $curX += $col['width'];
        }

        $pdf->SetXY(self::LEFT_MARGIN, $startY + $rowHeight);
    }

    /**
     * Send monthly report PDF as a download response.
     */
    public function downloadMonthlyReportResponse(
        \Illuminate\Support\Collection $documents,
        User $user,
        ?string $filename = null,
        ?array $options = null
    ) {
        $filename  = $filename ?? 'monthly_report_' . now()->format('Y_m') . '.pdf';
        $options   = array_merge(['month_label' => now()->format('F Y')], (array) $options);
        $pdfBinary = $this->makeMonthlyReportPdf($documents, $user, $options);

        return response($pdfBinary, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . addslashes($filename) . '"',
            'Content-Length'      => strlen($pdfBinary),
        ]);
    }
}
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\Tcpdf\Fpdi;
use App\Enums\Actions;
use App\Models\DocAssignmentAction;

class DocumentDownloadService
{
    /**
     * Build signatories from DocAssignmentAction collection.
     * Only include SIGNED actions.
     *
     * @param  \Illuminate\Support\Collection|array  $actions
     * @return array
     */
    public function buildSignatoriesFromActions($actions): array
    {
        $collection = is_array($actions) ? collect($actions) : $actions;

        return $collection
            ->filter(fn($a) => $a->action === Actions::SIGNED->value)
            ->map(fn(DocAssignmentAction $a) => [
                'name' => optional($a->user)->first_name . ' ' . optional($a->user)->last_name ?? 'Unknown',
                'role' => optional($a->user)->role ?? 'Unknown',
                'action' => $a->action,
                'datetime' => optional($a->created_at)->format('Y-m-d H:i:s') ?? now()->toDateTimeString(),
            ])
            ->sortBy('datetime')
            ->values()
            ->all();
    }

    /**
     * Build all action logs from DocAssignmentAction collection.
     * Include ALL actions for audit trail.
     *
     * @param  \Illuminate\Support\Collection|array  $actions
     * @return array
     */
    public function buildActionLogsFromActions($actions): array
    {
        $collection = is_array($actions) ? collect($actions) : $actions;

        return $collection
            ->map(fn(DocAssignmentAction $a) => [
                'name' => optional($a->user)->first_name . ' ' . optional($a->user)->last_name ?? 'Unknown',
                'role' => optional($a->user)->role ?? 'Unknown',
                'action' => $a->action,
                'datetime' => optional($a->created_at)->format('Y-m-d H:i:s') ?? now()->toDateTimeString(),
            ])
            ->sortBy('datetime')
            ->values()
            ->all();
    }

    /**
     * Generate PDF from Cloudinary URL and append SIGNED and ACTION LOG pages.
     *
     * @param  string  $cloudinaryUrl
     * @param  array   $signatories  // output of buildSignatoriesFromActions (SIGNED only)
     * @param  array   $actionLogs   // output of buildActionLogsFromActions (ALL actions)
     * @param  string|null $watermarkText
     * @param  array|null  $options
     * @return string
     */
    public function makeSignedPdfFromUrl(string $cloudinaryUrl, array $signatories, array $actionLogs = [], ?string $watermarkText = null, ?array $options = null): string
    {
        $options = array_merge([
            'include_footer' => true,
            'title' => 'DIGITAL SIGNATORIES',
        ], (array) $options);

        // fetch PDF bytes
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

        $reader = StreamReader::createByString($pdfBytes);
        $pageCount = $pdf->setSourceFile($reader);

        // import existing pages
        for ($i = 1; $i <= $pageCount; $i++) {
            $tplId = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tplId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId);
        }

        // === FIRST APPENDED PAGE: DIGITAL SIGNATORIES ===
        $this->appendSignatoriesPage($pdf, $signatories, $watermarkText, $options);

        // === SECOND APPENDED PAGE: ACTION AUDIT TRAIL ===
        if (!empty($actionLogs)) {
            $this->appendActionLogsPage($pdf, $actionLogs, $watermarkText, $options);
        }

        return $pdf->Output('', 'S');
    }

    /**
     * Append the Digital Signatories page (SIGNED actions only).
     */
    private function appendSignatoriesPage(Fpdi $pdf, array $signatories, ?string $watermarkText, array $options): void
    {
        $pdf->AddPage();
        $pageWidth = $pdf->getPageWidth();
        $pageHeight = $pdf->getPageHeight();

        // watermark
        $this->addWatermark($pdf, $watermarkText, $pageWidth, $pageHeight);

        $pdf->SetY(20);

        // header with line
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 12, $options['title'], 0, 1, 'C');
        $pdf->SetLineWidth(0.5);
        $pdf->Line(15, $pdf->GetY(), $pageWidth - 15, $pdf->GetY());
        $pdf->Ln(8);

        // description
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 5, 'This certification page provides a complete record of all authorized signatories who have reviewed and signed this document. The original document content remains unaltered and legally binding.', 0, 'L', false);
        $pdf->Ln(10);

        // table header with background
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(65, 10, 'Signatory Name', 1, 0, 'C', true);
        $pdf->Cell(45, 10, 'Role/Position', 1, 0, 'C', true);
        $pdf->Cell(35, 10, 'Action', 1, 0, 'C', true);
        $pdf->Cell(0, 10, 'Date & Time', 1, 1, 'C', true);

        // table rows
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetFillColor(255, 255, 255);
        foreach ($signatories as $sig) {
            $pdf->Cell(65, 9, mb_substr($sig['name'], 0, 60), 1, 0, 'L');
            $pdf->Cell(45, 9, mb_substr($sig['role'], 0, 30), 1, 0, 'L');
            $pdf->Cell(35, 9, mb_substr($sig['action'], 0, 20), 1, 0, 'C');
            $pdf->Cell(0, 9, mb_substr($sig['datetime'], 0, 30), 1, 1, 'C');
        }

        // footer
        if ($options['include_footer']) {
            $this->addFooter($pdf, $pageWidth, $pageHeight);
        }
    }

    /**
     * Append the Action Audit Trail page (ALL actions).
     */
    private function appendActionLogsPage(Fpdi $pdf, array $actionLogs, ?string $watermarkText, array $options): void
    {
        $pdf->AddPage();
        $pageWidth = $pdf->getPageWidth();
        $pageHeight = $pdf->getPageHeight();

        // watermark
        $this->addWatermark($pdf, $watermarkText, $pageWidth, $pageHeight);

        $pdf->SetY(20);

        // header with line
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 12, 'ACTION AUDIT TRAIL', 0, 1, 'C');
        $pdf->SetLineWidth(0.5);
        $pdf->Line(15, $pdf->GetY(), $pageWidth - 15, $pdf->GetY());
        $pdf->Ln(8);

        // description
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 5, 'This audit trail provides a comprehensive chronological record of all actions performed on this document, including views, downloads, signatures, and other interactions. This log serves as an official record for compliance and verification purposes.', 0, 'L', false);
        $pdf->Ln(10);

        // table header with background
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(65, 10, 'User Name', 1, 0, 'C', true);
        $pdf->Cell(45, 10, 'Role/Position', 1, 0, 'C', true);
        $pdf->Cell(35, 10, 'Action Type', 1, 0, 'C', true);
        $pdf->Cell(0, 10, 'Date & Time', 1, 1, 'C', true);

        // table rows
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetFillColor(255, 255, 255);
        foreach ($actionLogs as $log) {
            $pdf->Cell(65, 9, mb_substr($log['name'], 0, 60), 1, 0, 'L');
            $pdf->Cell(45, 9, mb_substr($log['role'], 0, 30), 1, 0, 'L');
            $pdf->Cell(35, 9, mb_substr($log['action'], 0, 20), 1, 0, 'C');
            $pdf->Cell(0, 9, mb_substr($log['datetime'], 0, 30), 1, 1, 'C');
        }

        // footer
        if ($options['include_footer']) {
            $this->addFooter($pdf, $pageWidth, $pageHeight);
        }
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
        $pdf->Rotate(45, $pageWidth/2, $pageHeight/2);
        $pdf->Text($pageWidth/2 - 60, $pageHeight/2 - 20, $watermarkText);
        $pdf->StopTransform();
        if (method_exists($pdf, 'setAlpha')) {
            $pdf->setAlpha(1);
        }
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Add footer to the current page.
     */
    private function addFooter(Fpdi $pdf, float $pageWidth, float $pageHeight): void
    {
        $pdf->SetY($pageHeight - 25);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(15, $pdf->GetY(), $pageWidth - 15, $pdf->GetY());
        $pdf->Ln(3);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 5, 'Digitally generated by DocuSphere DTS | ' . now()->format('F d, Y \a\t H:i:s T'), 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Directly send the PDF to the browser as a download.
     */
    public function downloadSignedPdfResponse(string $cloudinaryUrl, array $signatories, array $actionLogs = [], ?string $watermarkText = null, string $filename = 'signed_document.pdf')
    {
        $pdfBinary = $this->makeSignedPdfFromUrl($cloudinaryUrl, $signatories, $actionLogs, $watermarkText);

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . addslashes($filename) . '"',
            'Content-Length' => strlen($pdfBinary),
        ]);
    }
}
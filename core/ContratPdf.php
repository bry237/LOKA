<?php

declare(strict_types=1);

/**
 * Génère un résumé de contrat de location au format PDF (une page A4), sans bibliothèque
 * externe — même technique que core/QuittancePdf.php. Utilisé quand aucun fichier PDF n'a été
 * déposé pour le contrat (contrat.chemin_fichier_pdf vide), ce qui est le cas de tout contrat
 * créé directement en ligne (par l'agence ou le propriétaire) plutôt qu'importé.
 */
final class ContratPdf
{
    private string $content = '';

    private const PAGE_W = 595;
    private const PAGE_H = 842;
    private const MARGIN = 55;

    public static function generate(array $d): string
    {
        $pdf = new self();
        $pdf->draw($d);

        return $pdf->build();
    }

    private function draw(array $d): void
    {
        $left = self::MARGIN;
        $right = self::PAGE_W - self::MARGIN;
        $y = self::PAGE_H - 70;

        $typeLabels = [
            'HABITATION_VIDE' => 'Location à usage d’habitation (non meublée)',
            'HABITATION_MEUBLE' => 'Location à usage d’habitation (meublée)',
            'COMMERCIAL' => 'Bail commercial',
            'PROFESSIONNEL' => 'Bail professionnel',
            'SAISONNIER' => 'Location saisonnière',
        ];

        $this->rect($left, $y - 14, $right - $left, 44, [0.06, 0.55, 0.55]);
        $this->text($left + 14, $y + 3, 20, 'CONTRAT DE LOCATION', true, [1, 1, 1]);

        $y -= 40;
        $this->text($left, $y, 10, 'Contrat n° ' . (string) ($d['numero'] ?? ''));
        $this->textRight($right, $y, 10, $typeLabels[$d['type_contrat'] ?? ''] ?? 'Contrat de location');

        $y -= 40;
        $this->text($left, $y, 9, 'AGENCE / BAILLEUR', true, [0.4, 0.4, 0.4]);
        $this->text(300, $y, 9, 'LOCATAIRE', true, [0.4, 0.4, 0.4]);

        $yA = $y - 15;
        $this->text($left, $yA, 11, (string) ($d['agence_nom'] ?? 'Agence LOKA'), true);

        $yL = $y - 15;
        $nomLocataire = trim(($d['locataire_prenom'] ?? '') . ' ' . ($d['locataire_nom'] ?? ''));
        $this->text(300, $yL, 11, $nomLocataire !== '' ? $nomLocataire : '—', true);
        if (!empty($d['locataire_email'])) {
            $yL -= 15;
            $this->text(300, $yL, 10, (string) $d['locataire_email'], false, [0.4, 0.4, 0.4]);
        }

        $y = min($yA, $yL) - 20;
        $this->text($left, $y, 9, 'LOGEMENT LOUÉ', true, [0.4, 0.4, 0.4]);
        $y -= 15;
        $this->text($left, $y, 11, (string) ($d['titre_bien'] ?? $d['reference_bien'] ?? 'Bien'), true);
        $adresse = trim((string) ($d['adresse_ligne1'] ?? ''));
        if ($adresse !== '') {
            $y -= 15;
            $this->text($left, $y, 11, $adresse);
            $y -= 15;
            $this->text($left, $y, 11, trim(($d['adresse_cp'] ?? '') . ' ' . ($d['adresse_ville'] ?? '')));
        }

        $y -= 35;
        $this->text($left, $y, 9, 'DURÉE', true, [0.4, 0.4, 0.4]);
        $y -= 16;
        $duree = 'Du ' . $this->date($d['date_debut'] ?? null)
            . ($d['date_fin'] ?? null ? ' au ' . $this->date($d['date_fin']) : ' (durée indéterminée)');
        $this->text($left, $y, 12, $duree, true);

        $y -= 35;
        $this->rect($left, $y - 6, $right - $left, 22, [0.93, 0.96, 0.96]);
        $this->text($left + 10, $y, 10, 'Conditions financières', true);
        $this->textRight($right - 10, $y, 10, 'Montant', true);

        $lignes = [
            ['Loyer mensuel', (float) ($d['loyer'] ?? 0)],
            ['Charges mensuelles', (float) ($d['charges'] ?? 0)],
            ['Dépôt de garantie', (float) ($d['depot_garantie'] ?? 0)],
        ];

        foreach ($lignes as [$label, $valeur]) {
            $y -= 24;
            $this->text($left + 10, $y, 11, $label);
            $this->textRight($right - 10, $y, 11, $this->money($valeur));
            $this->line($left, $y - 7, $right, $y - 7);
        }

        $y -= 24;
        $this->text($left + 10, $y, 11, 'Fréquence de paiement', true);
        $this->textRight($right - 10, $y, 11, $this->frequence((string) ($d['frequence_paiement'] ?? 'MONTHLY')), true);

        $y -= 45;
        $texte = "Ce document résume les conditions du contrat de location n° " . (string) ($d['numero'] ?? '')
            . " enregistré sur LOKA entre " . (string) ($d['agence_nom'] ?? 'l’agence')
            . " et " . ($nomLocataire !== '' ? $nomLocataire : 'le locataire')
            . " pour le logement désigné ci-dessus. Il ne remplace pas, le cas échéant, un bail signé séparément entre les parties.";

        foreach ($this->wrap($texte, 88) as $ligne) {
            $this->text($left, $y, 10, $ligne);
            $y -= 14;
        }

        $this->line($left, 60, $right, 60);
        $this->text($left, 45, 8, 'Document généré par LOKA — résumé du contrat enregistré en base.', false, [0.5, 0.5, 0.5]);
    }

    /* ---------- Primitives PDF (identiques à QuittancePdf) ---------- */

    private function text(float $x, float $y, int $size, string $s, bool $bold = false, array $rgb = [0, 0, 0]): void
    {
        $this->content .= sprintf(
            "BT /%s %d Tf %.3F %.3F %.3F rg %.2F %.2F Td (%s) Tj ET\n",
            $bold ? 'F2' : 'F1',
            $size,
            $rgb[0], $rgb[1], $rgb[2],
            $x, $y,
            $this->escape($s)
        );
    }

    private function textRight(float $xRight, float $y, int $size, string $s, bool $bold = false, array $rgb = [0, 0, 0]): void
    {
        $largeur = strlen($this->encode($s)) * $size * ($bold ? 0.56 : 0.52);
        $this->text($xRight - $largeur, $y, $size, $s, $bold, $rgb);
    }

    private function rect(float $x, float $y, float $w, float $h, array $rgb): void
    {
        $this->content .= sprintf("%.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re f\n", $rgb[0], $rgb[1], $rgb[2], $x, $y, $w, $h);
    }

    private function line(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->content .= sprintf("0.85 0.85 0.85 RG 0.6 w %.2F %.2F m %.2F %.2F l S\n", $x1, $y1, $x2, $y2);
    }

    private function encode(string $s): string
    {
        $out = @iconv('UTF-8', 'CP1252//TRANSLIT', $s);

        return $out === false ? $s : $out;
    }

    private function escape(string $s): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $this->encode($s));
    }

    private function build(): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::PAGE_W . ' ' . self::PAGE_H . '] '
                . '/Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> /Contents 4 0 R >>',
            4 => "<< /Length " . strlen($this->content) . " >>\nstream\n" . $this->content . "endstream",
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            6 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= $num . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xref . "\n%%EOF";

        return $pdf;
    }

    private function wrap(string $texte, int $max): array
    {
        $lignes = [];
        $courante = '';

        foreach (explode(' ', $texte) as $mot) {
            if ($courante !== '' && iconv_strlen($courante . ' ' . $mot) > $max) {
                $lignes[] = $courante;
                $courante = $mot;
            } else {
                $courante = $courante === '' ? $mot : $courante . ' ' . $mot;
            }
        }

        if ($courante !== '') {
            $lignes[] = $courante;
        }

        return $lignes;
    }

    private function date(?string $date): string
    {
        $t = $date ? strtotime($date) : false;

        return $t === false ? '—' : date('d/m/Y', $t);
    }

    private function money(float $v): string
    {
        return number_format($v, 2, ',', ' ') . ' €';
    }

    private function frequence(string $frequence): string
    {
        return match ($frequence) {
            'MONTHLY' => 'Mensuelle',
            'QUARTERLY' => 'Trimestrielle',
            'YEARLY' => 'Annuelle',
            default => $frequence,
        };
    }
}

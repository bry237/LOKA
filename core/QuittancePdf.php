<?php

declare(strict_types=1);

/**
 * Génère une quittance de loyer au format PDF (une page A4),
 * sans bibliothèque externe.
 */
final class QuittancePdf
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

    /* ---------- Mise en page ---------- */

    private function draw(array $d): void
    {
        $left = self::MARGIN;
        $right = self::PAGE_W - self::MARGIN;
        $y = self::PAGE_H - 70;

        $total = (float) $d['montant_total'];
        $recu = (float) $d['montant'];
        $complet = $recu + 0.001 >= $total;

        // Bandeau titre
        $this->rect($left, $y - 14, $right - $left, 44, [0.06, 0.55, 0.55]);
        $this->text($left + 14, $y + 3, 20, $complet ? 'QUITTANCE DE LOYER' : 'REÇU DE PAIEMENT PARTIEL', true, [1, 1, 1]);

        $y -= 40;
        $this->text($left, $y, 10, 'Quittance n° ' . $d['numero_quittance']);
        $this->textRight($right, $y, 10, 'Émise le ' . $this->date($d['date_emission']));

        // Agence / Locataire
        $y -= 40;
        $this->text($left, $y, 9, 'AGENCE', true, [0.4, 0.4, 0.4]);
        $this->text(300, $y, 9, 'LOCATAIRE', true, [0.4, 0.4, 0.4]);

        $agenceLignes = array_filter([
            $d['agence_nom'] ?? '',
            $d['agence_adresse'] ?? '',
            trim(($d['agence_code_postal'] ?? '') . ' ' . ($d['agence_ville'] ?? '')),
        ]);
        $locataireLignes = [
            trim(($d['locataire_prenom'] ?? '') . ' ' . ($d['locataire_nom'] ?? '')),
        ];

        $yA = $y - 15;
        foreach ($agenceLignes as $i => $ligne) {
            $this->text($left, $yA, 11, (string) $ligne, $i === 0);
            $yA -= 15;
        }
        $yL = $y - 15;
        foreach ($locataireLignes as $ligne) {
            $this->text(300, $yL, 11, $ligne, true);
            $yL -= 15;
        }

        // Logement
        $y = min($yA, $yL) - 20;
        $this->text($left, $y, 9, 'LOGEMENT LOUÉ', true, [0.4, 0.4, 0.4]);
        $y -= 15;
        $this->text($left, $y, 11, (string) ($d['bien_titre'] ?? ''), true);
        $y -= 15;
        $adresse = trim(($d['adresse_ligne1'] ?? '') . ' ' . ($d['adresse_ligne2'] ?? ''));
        $this->text($left, $y, 11, $adresse);
        $y -= 15;
        $this->text($left, $y, 11, trim(($d['adresse_cp'] ?? '') . ' ' . ($d['adresse_ville'] ?? '')));
        $y -= 15;
        $this->text($left, $y, 10, 'Contrat n° ' . $d['numero_contrat'], false, [0.4, 0.4, 0.4]);

        // Période
        $y -= 35;
        $this->text($left, $y, 9, 'PÉRIODE', true, [0.4, 0.4, 0.4]);
        $y -= 16;
        $this->text($left, $y, 12, 'Du ' . $this->date($d['periode_debut']) . ' au ' . $this->date($d['periode_fin']), true);

        // Détail
        $y -= 35;
        $this->rect($left, $y - 6, $right - $left, 22, [0.93, 0.96, 0.96]);
        $this->text($left + 10, $y, 10, 'Détail', true);
        $this->textRight($right - 10, $y, 10, 'Montant', true);

        $lignes = [
            ['Loyer', (float) $d['montant_loyer']],
            ['Charges', (float) $d['montant_charges']],
        ];

        foreach ($lignes as [$label, $valeur]) {
            $y -= 24;
            $this->text($left + 10, $y, 11, $label);
            $this->textRight($right - 10, $y, 11, $this->money($valeur));
            $this->line($left, $y - 7, $right, $y - 7);
        }

        $y -= 24;
        $this->text($left + 10, $y, 11, 'Total du terme', true);
        $this->textRight($right - 10, $y, 11, $this->money($total), true);

        $y -= 24;
        $this->text($left + 10, $y, 11, 'Somme reçue', true);
        $this->textRight($right - 10, $y, 12, $this->money($recu), true, [0.06, 0.55, 0.55]);

        // Paiement
        $y -= 40;
        $this->text($left, $y, 9, 'PAIEMENT', true, [0.4, 0.4, 0.4]);
        $y -= 16;
        $this->text($left, $y, 11, 'Reçu le ' . $this->date($d['date_paiement']) . ' — ' . $this->mode((string) $d['mode_paiement']));
        if (!empty($d['reference_transaction'])) {
            $y -= 15;
            $this->text($left, $y, 10, 'Référence : ' . $d['reference_transaction'], false, [0.4, 0.4, 0.4]);
        }

        // Mention légale
        $y -= 45;
        $texte = $complet
            ? "Je soussigné, " . ($d['agence_nom'] ?? "l'agence") . ", agissant pour le compte du bailleur, "
              . "déclare avoir reçu de " . trim(($d['locataire_prenom'] ?? '') . ' ' . ($d['locataire_nom'] ?? ''))
              . " la somme de " . $this->money($recu) . " au titre du loyer et des charges pour la période indiquée "
              . "ci-dessus, et lui en donne quittance, sous réserve de tous mes droits."
            : "Cette somme constitue un paiement partiel du terme ci-dessus. "
              . "Elle ne vaut pas quittance du loyer et ne libère pas le locataire du solde restant dû.";

        foreach ($this->wrap($texte, 88) as $ligne) {
            $this->text($left, $y, 10, $ligne);
            $y -= 14;
        }

        // Pied de page
        $this->line($left, 60, $right, 60);
        $this->text($left, 45, 8, 'Document généré par LOKA — il est valable sans signature.', false, [0.5, 0.5, 0.5]);
    }

    /* ---------- Primitives PDF ---------- */

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
        // Largeur approximative d'Helvetica : 0,52 × corps par caractère.
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

    /* ---------- Formatage ---------- */

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

    private function mode(string $mode): string
    {
        return match ($mode) {
            'CASH'          => 'espèces',
            'BANK_TRANSFER' => 'virement bancaire',
            'CARD'          => 'carte bancaire',
            'CHEQUE'        => 'chèque',
            'DIRECT_DEBIT'  => 'prélèvement',
            'PAYPAL'        => 'PayPal',
            default         => 'autre',
        };
    }
}
# Technische Recherche: Barcode-Labels für Dell EMC ML3 / LTO

Stand: 2026-09-23 · Phase 1

## 1. Quellen

Verifizierte, offizielle Quellen (IBM hat die LTO-Ultrium-Label-Spezifikation definiert
und wird von allen LTO-Libraries, inkl. Dell ML3, referenziert):

1. **IBM — „Bar code label specifications“** (TS4500 Tape Library)
   <https://www.ibm.com/docs/en/ts4500-tape-library?topic=labels-bar-code-label-specifications>
2. **IBM — „Tape drives and bar code label requirements“**
   <https://www.ibm.com/support/pages/tape-drives-and-bar-code-label-requirements>
3. **IBM — „Cartridges and barcode labels for 3580 and 3592 drives“**
   <https://www.ibm.com/support/pages/cartridges-and-barcode-labels-3580-and-3592-drives>
4. **IBM LTO Ultrium Cartridge Label Specification (Rev. 2, PDF)** — vollständige physische
   Spezifikation (nur als PDF; Kernwerte sind in 1–3 wiedergegeben).
5. **Dell EMC ML3 Tape Library User's Guide** (PDF, 2018) — bestätigt, dass die ML3 die
   Standard-LTO-Barcode-Labels verwendet.

> Hinweis: Die Dell-/IBM-PDFs ließen sich hier nicht maschinell vollständig auslesen.
> Alle Aussagen stammen aus den o.g. HTML-Quellen (IBM) und dem ML3-Guide. Nicht eindeutig
> belegbare Werte sind als **Annahme** markiert und in der Anwendung konfigurierbar.

## 2. Kernbefunde

### 2.1 Barcode-Symbologie

- **USS-39 = „Code 39“ („3 of 9“)** — **nicht** Code 128.
  Der in der Aufgabenstellung als „typisch“ vermutete Code 128 wurde durch die Recherche
  **widerlegt**. LTO-Labels und damit auch die Dell ML3 verwenden Code 39.
- Code 39 nutzt Start-/Stoppzeichen `*` (nicht Teil der Nutzdaten).

### 2.2 Datenformat (Nutzdaten)

- **Genau 8 Zeichen**, nur **Großbuchstaben A–Z und Ziffern 0–9**.
- **Datenband:** `XXXXXXLx`
  - 6 Zeichen VOLSER (frei wählbar, alphanumerisch)
  - `L` + Generationsziffer (1–9) als Medienkennung (Media-Identifier)
  - Beispiel: `ABC123L8`, `BACKUP100L8`
- **Gültige Media-Identifier (letzte 2 Zeichen)** laut IBM:
  `L1` `L2` `L3` `L4` `L5` `L6` `L7` `L8` `L9` `LT` `LU` `LV` `LW` `LX` `LY` `M8` `LZ`
  - `M8` = LTO-8 „Type M“-Medien (9 TB).
  - `LT`…`LZ` sind Sonder-/WORM-/reservierte Varianten; als konfigurierbare Liste abgebildet.
- **Cleaning-Cartridge (universal):** `CLNUxxL1` (IBM) bzw. verbreitet `CLNxxxL1`.
  - Präfix `CLN` kennzeichnet eine Reinigungskassette.
  - Endung **`L1`** ist der **generationsunabhängige** „Universal-Cleaning“-Designator:
    eine LTO-Reinigungskassette ist über alle Generationen einsetzbar und trägt unabhängig
    von der zu reinigenden Generation immer `L1`.
  - Konkrete Variante (mit/ohne `U`, Länge der laufenden Nummer) ist **konfigurierbar**
    (Profil), da Dell/Hersteller leicht abweichende Muster akzeptieren.

### 2.3 Unterscheidung Data vs. Cleaning

- Die Library unterscheidet anhand des **Barcode-Inhalts**: Präfix `CLN` (+ Endung `L1`)
  ⇒ Cleaning-Cartridge; `Lx`-Endung ohne `CLN` ⇒ Datenband.
- Es gibt **keine** Sonder-Symbologie für Cleaning-Tapes — gleiches Code 39, andere Daten.

### 2.4 Physische Druckparameter (verifiziert, IBM)

| Parameter | Wert |
|---|---|
| Symbolik | Code 39 (USS-39) |
| Modulbreite (narrow element) | **0,423 mm** (0,017 in) nominal |
| Breit-Schmal-Verhältnis | **2,75 : 1** |
| Mindest-Balkenhöhe (bar length) | **11,1 mm** (0,44 in) |
| Oberfläche | **matt/nicht glänzend**, hoher Kontrast (schwarz auf weiß) |
| Quiet Zone | beidseitig erforderlich; **keine Markierungen** im weißen Rand |
| Labelposition | versenkter Labelbereich (recessed area) an der Cartridge-Vorderseite |

## 3. Abgeleitete technische Parameter der Anwendung

### 3.1 Verifiziert → als Default übernommen

```yaml
barcode:
  type: CODE39          # USS-39 / "3 of 9"
  module_width_mm: 0.423
  wide_narrow_ratio: 2.75
  height_mm: 11.1        # Mindest-Balkenhöhe (IBM)
```

### 3.2 Annahme (nicht eindeutig belegt) → konfigurierbar

| Parameter | Default | Begründung / Status |
|---|---|---|
| Quiet Zone | `4,0 mm` | Code-39-Standard empfiehlt ≥ 10× Modulbreite ≈ 4,23 mm; LTO-Spec nennt keinen exakten Wert. Als **Annahme** default 4,0 mm, min. 3,0 mm konfigurierbar. |
| Gesamt-Labelmaße | `102 × 14 mm` (4,016″ × 0,551″) | Standard-LTO-Ultrium-Cartridge-Label. Mit der nominalen Modulbreite 0,423 mm benötigt ein 8-stelliger Code-39-Barcode inkl. Quiet Zone ≈ 73 mm Breite, daher ist eine Labelbreite unter ≈ 73 mm nicht scannbar (die Anwendung warnt/blockiert dann). **Konfigurierbar.** |
| Klartext (Human Readable) | unter dem Barcode | LTO-Labels tragen den VOLSER als Klartext; konfigurierbar (an/aus, Position). |
| Cleaning-Syntax | `CLN + 3 Ziffern + L1` (`CLN001L1`) | IBM nennt `CLNUxxL1`; verbreitet ist `CLNxxxL1`. **Profil-basiert konfigurierbar.** |
| Unterstützte Generationen | LTO-1 … LTO-9 | Konfigurierbare Liste der Media-Identifier. |

### 3.3 Konsequenzen für die Implementierung

1. **Code 39 selbst implementieren** (austauschbare Komponente, Vektor-Ausgabe) — volle
   Kontrolle über Modulbreite, Ratio und Quiet Zone.
2. **Vektorbarcode in PDF** (gefüllte Rechtecke), keine JPEG/Bitmap-Kompression.
3. **Physische Maße** (mm → points) exakt abbilden; keine CSS-Pixel für das PDF.
4. **Validierung** pro Medientyp (`DATA` / `CLEANING`), konfigurierbar über Medienprofile.
5. **Qualitätswarnungen**, wenn Parameter von den recherchierten Anforderungen abweichen
   (z.B. Modulbreite < 0,423 mm oder Balkenhöhe < 11,1 mm).

## 4. Offene Punkte / nicht abschließend verifiziert

- Exakte Gesamt-Labelgröße der Dell-ML3-/LTO-Cartridge (recessed area) — als konfigurierbare
  Annahme `102 × 14 mm` (4,016″ × 0,551″) abgebildet. Das PDF des vollständigen IBM-Specs
  lag nicht maschinenlesbar vor; die Breite ist aus der erforderlichen Barcodebreite (≈ 73 mm)
  hergeleitet und liegt nahe dem Standardwert.
- Exakter Quiet-Zone-Mindestwert laut LTO-Spec — als konfigurierbare Annahme abgebildet.
- Exakte, von Dell bevorzugte Cleaning-Syntax — über **Medienprofile** abbildbar
  (`dell_ml3`, `generic_lto`).

Diese Punkte werden in `config/` als konfigurierbar abgebildet und sind nicht fest im Code.

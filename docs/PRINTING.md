# Drucken – ML3-kompatible Labels

Die Dell EMC PowerVault ML3 (und LTO allgemein) liest die Barcode-Labels optisch.
Damit die Labels zuverlässig erkannt werden, müssen beim Druck folgende
Anforderungen eingehalten werden.

## Physikalische Anforderungen (IBM LTO Ultrium)

| Parameter | Wert |
|---|---|
| Symbologie | Code 39 (USS-39) |
| Zeichen | genau 8, nur `A–Z` und `0–9` |
| Modulbreite (schmales Element) | **0,423 mm** nominal |
| Breit-Schmal-Verhältnis | **2,75 : 1** |
| Mindest-Balkenhöhe | **11,1 mm** |
| Quiet Zone | beidseitig, **keine Markierungen** im weißen Rand |
| Oberfläche | **matt / nicht glänzend**, schwarz auf weiß |

## Datenformat

- **Datenband:** 6 Zeichen VOLSER + Medienkennung, z. B. `ABC123L8`.
  Gültige Kennungen: `L1`–`L9`, `M8`, `LT`, `LU`, `LV`, `LW`, `LX`, `LY`, `LZ`.
- **Cleaning:** `CLN` + laufende Nummer + `L1` (universal), z. B. `CLN001L1`.
  Eine LTO-Reinigungskassette ist generationsunabhängig und trägt immer `L1`.

## Drucker-Einstellungen

- **Keine Skalierung** verwenden („Tatsächliche Größe“ / „100 %“, kein „An Seite anpassen“).
- Auflösung ≥ 300 dpi, empfohlen 600 dpi.
- Nur **matte** Etiketten verwenden; glänzende Oberflächen können den Scanner blenden.
- Etikettenmaterial: geeignetes, nicht reflektierendes Papier/Folie für Etikettendrucker.

## Ausgabe-Modi

1. **Einzeldruck** (`/print/single?id=N`): Das PDF hat exakt die Labelgröße
   (`label.width_mm × label.height_mm`). Für Einzeletiketten / Thermodirektdruck.
2. **Blattdruck** (`/print/sheet`): Label werden in einem konfigurierbaren Raster
   (`page.grid`, Standard A4, 2 Spalten × 8 Zeilen) angeordnet. Die Einzelmaße bleiben
   exakt; es wird nicht skaliert.
3. **Testseite** (`/print/test`): druckt Daten- und Cleaning-Beispiel-Labels, um
   Scanner und Ausrichtung zu prüfen.

## Labelmaße

Der Standard verwendet das LTO-Cartridge-Label **102 × 14 mm** (4,016″ × 0,551″).
Eigene Etikettenformate lassen sich im Profil konfigurieren:

```yaml
profiles:
  dell_ml3:
    label:
      width_mm: 102.0
      height_mm: 14.0
```

> Achtung: Bei der nominalen Modulbreite 0,423 mm benötigt ein 8-stelliges Code-39-Label
> inkl. Quiet Zone ≈ 73 mm Breite. Schmalere Labels sind nicht zuverlässig scannbar;
> die Anwendung blockiert die Erzeugung in diesem Fall.

## Verifikation vor dem Rollout

1. Testseite drucken (`/print/test`).
2. Mit dem Barcodescanner der ML3 (bzw. einem Handscanner) prüfen, dass der
   erwartete Code gelesen wird.
3. Ein Etikett auf eine Cartridge kleben und den Einlade-/Inventarisierungsvorgang
   der Library beobachten (Data- und Cleaning-Label getrennt prüfen).

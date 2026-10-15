# Pobierz wtyczkę Yoona SEO

Plik do instalacji w WordPressie: **yoona-seo.zip** (22 KB).

Na GitHubie otwórz plik `yoona-seo.zip` i wybierz **Download raw file** (ikona pobierania). Wgraj właśnie ten ZIP, a nie archiwum całego repozytorium z przycisku „Code”.

## Instalacja i eksport

1. W panelu WordPressa otwórz **Wtyczki → Dodaj nową → Wyślij wtyczkę na serwer**.
2. Wskaż `yoona-seo.zip`, zainstaluj i aktywuj.
3. Przejdź do **Narzędzia → Yoona SEO**.
4. Kliknij **Pobierz diagnostykę do audytu (JSON)**.
5. Przekaż pobrany JSON w rozmowie, w której przygotowywany jest audyt. Nie publikuj eksportu w repozytorium.

Aktywacja nie zmienia treści strony. Ta paczka **nie zawiera jeszcze poprawionych tekstów**: audyt wymaga dostępu do rzeczywistej treści yoona.pl. Po ich przygotowaniu ta sama wtyczka pozwala wczytać pakiet JSON, sprawdzić podgląd i wdrożyć obsługiwane zmiany z kopią poprzednich wartości oraz możliwością cofnięcia. Pełne nadpisanie tekstów kreatorów takich jak Elementor nie jest obsługiwane w tej wersji.

Wymagania: WordPress 6.0+, PHP 7.4+, konto administratora. Testy wykonano lokalnie na WordPress 7.1.3, PHP 8.3.35 i MariaDB 11.4; nie na produkcyjnym yoona.pl. Przed wdrożeniem wykonaj pełną kopię witryny w hostingu.

Kod źródłowy w tej gałęzi jest w `plugin/yoona-seo/`. Wtyczka nie wysyła treści do zewnętrznych usług i nie wymaga klucza API.

SHA-256 pliku ZIP:

```text
148f39cecf7ac4e630b2cc5d65d79c3455aa79f322305a08f8b5ff1316498e4b
```

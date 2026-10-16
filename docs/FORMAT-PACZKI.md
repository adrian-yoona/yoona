# Format pakietu zmian — wersja 2

Gotowy plik znajduje się w `plugin/yoona-seo/content/changes.json` i jest dołączony do instalacyjnego ZIP. Administrator nie musi ręcznie edytować JSON. Import osobnego pliku ma limit 2 MB; dołączony plik limit 5 MB.

Pola główne:

- `schema_version: 2` — wersja obsługująca natywny Rank Math i poprawki modułów Divi.
- `seo_storage: "rank_math"` — zapis tytułów/opisów bezpośrednio w Rank Math. Wymaga aktywnego Rank Math.
- `site_url` — dokładny `home_url` witryny, łącznie z protokołem, domeną, portem i katalogiem. Gotowy pakiet dotyczy `https://yoona.pl`.
- `audit_verified: true` — opracowanie pakietu ukończono. To status, a nie podpis cyfrowy; wczytuj wyłącznie zaufane paczki.
- `pages` — maksymalnie 500 unikalnych lokalnych ścieżek istniejących opublikowanych stron lub wpisów. Gotowy pakiet obejmuje 200.

Każda pozycja ma `path`, `seo_title` i `meta_description`. Tytuł i opis są zwykłym tekstem w jednym wierszu. Adresy URL, robots i canonical nie są modyfikowane.

## Poprawki tekstów Divi

Pozycja z tekstami ma `expected_content_sha256` oraz tablicę `divi_text`. Każdy element tej tablicy zawiera:

- `module_type`: `et_pb_text` lub `et_pb_toggle` (domyślnie `et_pb_text`).
- `module_index`: indeks od zera w obrębie tego typu, a nie wszystkich modułów razem.
- `expected_html_sha256`: SHA-256 dokładnego źródłowego wnętrza modułu w UTF-8.
- `html`: nowe, zatwierdzone wnętrze modułu.

Wymagane są zgodne hashe całej poprzedniej treści i modułu. Helper odczytuje oryginalne offsety i podmienia wyłącznie wskazane wnętrza od końca źródła. Zachowuje wszystkie pozostałe bajty, w tym atrybuty i kolejność shortcode. Nie obsługuje zmiany tytułów FAQ zapisanych w atrybutach shortcode.

Nowy HTML musi być dokładnie zgodny z `wp_kses_post` w WordPressie. Podczas przygotowania kanonizowano formatowanie atrybutów `style` przez rzeczywisty WordPress; niezależnie sprawdzono zachowanie tych samych deklaracji CSS oraz reszty HTML. Nie wyłączano walidacji. Nie można wprowadzać shortcode, skryptów, formularzy ani innych niedozwolonych znaczników. Moduły z osadzonym shortcode, niejednoznaczne, zagnieżdżone lub niedomknięte są blokowane.

Jeśli wszystkie nowe teksty i metadane są już zapisane, podgląd oznacza pozycję jako aktualną bez ponownego użycia starego hasha. Jeśli treść zmieniono w inny sposób od eksportu, wymagany jest nowy eksport i ponowny przegląd.

## Zapis i kopie

Zapis natywny Rank Math obejmuje `rank_math_title` i `rank_math_description`. Własne dawne nadpisania Yoona SEO są usuwane w tej samej kontrolowanej zmianie, a ich poprzedni stan zachowany w kopii. Stare pola Yoast, robots, canonical i pozostałe meta pozostają nietknięte.

Panel wysyła maksymalnie 10 pozycji na żądanie. JavaScript wykonuje partie kolejno i pokazuje postęp. Błąd zatrzymuje dalsze partie; zapisane wcześniejsze partie mają własne kopie dostępne w pełnej historii. Odtwarzanie chroni późniejsze edycje porównaniem stanu i zgłasza konflikty. Kopia wtyczki nie jest backupem całej bazy ani mediów.

## Zgodność z wersją 1

Importer nadal odczytuje paczki wersji 1 i wcześniejsze kopie zmian. Ich własne metadane Yoona SEO mogły być emitowane przez filtry podczas aktywności wtyczki. Domyślna paczka wersji 2 korzysta z natywnych pól Rank Math i jej poprawki pozostają po wyłączeniu Yoona SEO.

Opcjonalna pełna zamiana `content` nadal wymaga zgodnego SHA-256 i jest blokowana dla kreatorów. Nie można łączyć `content` z `divi_text`. Opcjonalny nowy `post_title` wymaga zgodnego `expected_post_title`. Gotowy pakiet nie używa pełnej zamiany treści ani zmiany tytułów wpisów/adresów.

## Budowanie

`tools/build_changes.py` scala trzy listy zatwierdzonych zmian z `copy/`, porównuje je z prywatnym eksportem i sprawdza pełne pokrycie, unikalność ścieżek, tytułów i opisów oraz hashe modułów. Surowy eksport pozostaje poza repozytorium. Dopiero test w prawdziwym WordPressie potwierdza jego reguły HTML i cały import.

# Testy integracyjne WordPress

Uruchom z katalogu projektu:

```bash
bash tests/record-wordpress.sh
```

Wymagane: Docker z działającym daemonem, Python 3, Node.js i Git. Skrypt pobiera oficjalne
obrazy WordPress/Apache, WP CLI, MariaDB i Composer przez standardową weryfikację Docker.
Domyślne obrazy są przypięte do digestów, aby kolejne uruchomienia korzystały
z tych samych wersji: WordPress 7.1.3, PHP 8.3.35 i MariaDB 11.4. Raport
`wordpress-integration-results.txt` jest podmieniany dopiero po pełnym sukcesie;
nieudany test zachowuje poprzedni poprawny raport.

Każde uruchomienie tworzy osobną bazę, wolumen i sieć. Nazwy zaczynają się od
`yoona-seo-test-`; port WWW jest losowy i udostępniony wyłącznie na
`127.0.0.1`. Lokalne hasła testowe nie służą do połączenia z prawdziwą stroną.
Skrypt nie łączy się z panelem ani bazą `yoona.pl`, nie zmienia plików źródłowych
wtyczki i po zakończeniu usuwa utworzone zasoby testowe. Połączenia HTTP wychodzące
z testowego WordPressa są zablokowane jego standardowym ustawieniem.

Testy sprawdzają składnię PHP, brak zmian po aktywacji i imporcie, mapowanie
adresów na strony, blokady niebezpiecznego HTML i nieaktualnych treści,
uprawnienia, konkurencyjny zapis, kopie zapasowe i odzyskiwanie po błędach.
Osobny proces symuluje obecność Yoast SEO przez stałą identyfikującą dostawcę
i sprawdza przekazywanie wartości przez filtry oraz wyłączenie dodatkowego
tagu description. Ta faza nie instaluje Yoast SEO i nie potwierdza zgodności
z każdą jego wersją; właściwy Rank Math jest uruchamiany w dalszej fazie.
Test HTTP loguje
się do lokalnego panelu, przesyła prawdziwy plik JSON, sprawdza nonce, wdraża
metadane, weryfikuje wynik na stronie i cofa zmiany. Wysyłanie JSON sprawdza limity
partii, nonce i nieaktualny podgląd. Oryginalny skrypt panelu jest dodatkowo
wykonywany w Node.js z atrapą DOM i fetch: 200 pozycji, 20 kolejnych żądań,
ochrona przed podwójnym wysłaniem i zatrzymanie przy błędzie.

W osobnej fazie uruchamiany jest prawdziwy Rank Math 1.0.280 z oficjalnego repozytorium
Git, przypięty do commitu `50fc59587112316ad754ca3c9a26e1282795d49d`. Można wskazać
gotowy checkout przez `YOONA_TEST_RANK_MATH_SOURCE`. W jego jednorazowej kopii
testowej Composer odbudowuje nieaktualną mapę klas, bez pobierania zależności,
wykonywania skryptów i uruchamiania wtyczek Composera. Używana jest obsługiwana przez
Rank Math opcja pominięcia rejestracji konta. Test sprawdza zapis do jego własnych
pól SEO, cofanie dokładnej obecności metadanych i wynik HTML również po wyłączeniu
Yoona SEO.

Test Divi sprawdza zachowanie oryginalnych bajtów shortcode, atrybutów, obrazów,
galerii i formularzy poza zmienianymi fragmentami tekstu. Parser WordPressa jest
uruchamiany z testowymi callbackami. Licencjonowany motyw Divi nie jest dołączony;
te testy nie zastępują wizualnego przeglądu strony z tym motywem.

Warstwa wyglądu ma osobne testy na rzeczywistych żądaniach HTTP: nowa strona
główna, podstrona z zachowaną treścią Divi, wpis, archiwum, wyszukiwanie, błąd
404 i formularze ochrony hasłem. Testy sprawdzają standardowe hooki WordPressa,
pojedyncze tytuły i opisy SEO, wyłączenie wyglądu dla RSS, REST, osadzania
i edytora Divi, a także włączanie i wyłączanie przez formularze administratora.
Przed i po samych operacjach wyglądu porównywane są wszystkie rekordy wpisów
oraz metadanych; zmiana szablonu nie może ich modyfikować. Przed zapisem stanu
początkowego oryginalny motyw blokowy jest raz renderowany, aby WordPress
utworzył własny rekord nawigacji. Porównanie nadal obejmuje wszystkie rekordy.

Opcjonalny test w prawdziwym Chromium wymaga pakietu Node `playwright`, Python 3 z Pillow
i przeglądarki pod `/usr/bin/chromium` (ścieżkę można wskazać przez
`YOONA_TEST_CHROMIUM`). Uruchom go wraz z pełnym przebiegiem:

```bash
YOONA_TEST_BROWSER=1 bash tests/record-wordpress.sh
```

Ten przebieg sprawdza też prawdziwy formularz importera w Chromium. Tworzy 200
publicznych stron testowych, w tym trzy duże strony Divi z 51 poprawkami modułów.
W formularzu pozostaje ukryte pole `name="action"`, które zasłania natywną
właściwość `form.action`. Test klika przycisk w panelu, sprawdza właściwy adres
`admin-post.php`, 20 kolejnych żądań po 10 pozycji, rzeczywiste pola Rank Math,
dostępność wszystkich 20 kopii i pełne cofnięcie dokładnych bajtów treści.
Odpowiedzi HTML z kodami 403, 404 i 502 muszą zatrzymywać zapis z czytelnym
komunikatem bez automatycznego ponawiania. Osobna próba podmienia potwierdzenie
502 już po poprawnym zapisie backendu i sprawdza zachowanie działającej kopii.
Na zachowanej instalacji można uruchomić tylko ten test:

```bash
node tests/integration-seo-admin-browser.cjs http://127.0.0.1:PORT NAZWA_KONTENERA_CLI
```

Kontener musi zawierać aktualną wtyczkę oraz katalog `tests` skopiowany do
`/var/www/html/yoona-tests`, zgodnie z `run-wordpress.sh`; musi też być aktywny
Rank Math. `seo-admin-browser-results.txt` zawiera celowany wynik dla wersji
3.1.1. Historyczny raport pełnego importera pozostaje osobnym dowodem jego
wcześniejszego sprawdzenia; celowany test nie zastępuje całego zestawu.

Test wyglądu sprawdza widoki komputerowe 1440/1920 px i mobilne 390/320 px, brak poziomego przewijania, pojedynczy
nagłówek H1, obsługę menu klawiaturą i widoczność zachowanej treści podstrony.
Weryfikuje rzeczywiste załadowanie lokalnych fontów Archivo Black i Manrope,
wersaliki wszystkich nazw oferty, rozmiar opisu hero i poprawne adresy nawigacji.
Poza `scrollWidth` mierzy prostokąty tekstu przez DOM Range, dzięki czemu wykrywa
też przycięcie tekstu ukryte przez `overflow`. Po normalnych zrzutach podmienia
wyłącznie zdjęcie hero na białe SVG i mierzy piksele za końcami wierszy tekstu:
kontrast minimum 3:1 dla dużych nagłówków i 4,5:1 dla opisu. Sprawdza też, że
fotografia pozostaje widoczna poza mocniej przyciemnioną częścią pod tekstem.
Ten test wykrywa zasłonę umieszczoną przypadkowo pod zdjęciem, nawet gdy ciemny
obraz testowy maskowałby taki błąd.
Osobny fixture `/pelna-oferta/` sprawdza ograniczoną do tej strony klasę CSS
oraz fotograficzny katalog dwóch publicznych stron usługowych, lokalny Manrope,
wersaliki i długie tytuły we wszystkich czterech szerokościach. Dawna treść
H3–H6 jest zachowana w bazie i wraca po wyłączeniu warstwy wyglądu.
Zrzuty trafiają do `/tmp/yoona-design-screenshots` lub katalogu wskazanego
przez `YOONA_TEST_SCREENSHOTS`. W teście obrazki realizacji są zastępowane
oznaczonymi lokalnymi obrazkami SVG, ponieważ nie można pobrać zdjęć z yoona.pl.
Zrzuty potwierdzają układ i zachowanie przeglądarki, a nie wygląd prawdziwych
zdjęć na stronie produkcyjnej. Testowe callbacki shortcode nie zastępują
licencjonowanego motywu Divi.

Po zmianie samego CSS można ponownie uruchomić test przeglądarki na zachowanej
lokalnej instalacji testowej przez `bash tests/record-design-browser.sh ADRES`.
Plik `design-browser-results.txt` jest podmieniany atomowo dopiero po sukcesie.
Test porównuje hash serwowanego CSS z aktualnym źródłem oraz mierzy szerokości
wszystkich kart galerii na telefonach, aby wykryć również ukryte lub zbyt wąskie
kolumny. Ten osobny wynik nie zastępuje raportu testów PHP i bazy danych.

`design-premium-http-results.txt` zawiera celowany przebieg dla wersji 3.1.0:
składnię wszystkich plików PHP wtyczki, 52 żądania i kontrole HTTP z właściwym
Rank Math oraz dokładne porównanie wszystkich wpisów i metadanych po zmianach
wyglądu. Na świeżej instalacji przed baseline rozgrzano też panel administratora:
standardowy widżet Quick Draft WordPressa tworzy własny auto-draft przy pierwszym
wejściu na kokpit. Ten raport nie powtarza ani nie zastępuje pełnego testu importera
200 pozycji zachowanego w `wordpress-integration-results.txt`.

Opcjonalny test rzeczywistej paczki można uruchomić z prywatnym eksportem:

```bash
YOONA_TEST_EXPORT_JSON=/prywatna/sciezka/eksport.json bash tests/record-wordpress.sh
```

Eksport trafia wyłącznie do `/tmp` kontenera CLI, poza katalogiem HTTP. Jego
rekordy są odtwarzane w oddzielnych tabelach `yoona_real_`, których kontener WWW
nie obsługuje. Test sprawdza wszystkie poprawki, wdraża paczkę partiami po 10,
sprawdza wynik i dostępność wszystkich kopii w panelu, a następnie cofa całość.
Raport zawiera wyniki i liczby, bez źródłowej treści eksportu.

Opcjonalnie `YOONA_TEST_KEEP=1 bash tests/run-wordpress.sh` pozostawia zasoby
do diagnozy; ich prefiks i adres WWW są wypisane w wyniku. Usuń później tylko
kontenery, wolumen i sieć o tym konkretnym prefiksie.

Zmienne `YOONA_TEST_WP_IMAGE`, `YOONA_TEST_CLI_IMAGE`, `YOONA_TEST_DB_IMAGE` i `YOONA_TEST_COMPOSER_IMAGE`
pozwalają uruchomić ten sam test z innymi zgodnymi oficjalnymi obrazami.

Nowa góra podstron ofertowych ma osobny test `integration-offer-hero.php`, HTTP
oraz Chromium. Na zachowanej, lokalnej instalacji z aktywnym Rank Math uruchom:

```bash
YOONA_TEST_EXPORT_JSON=/prywatna/sciezka/eksport.json YOONA_TEST_BROWSER=1 \
  bash tests/run-offer-hero.sh http://127.0.0.1:PORT NAZWA_KONTENERA_CLI NAZWA_KONTENERA_WEB
```

Kontenery pochodzą z `YOONA_TEST_KEEP=1 bash tests/run-wordpress.sh`. Runner jest
ograniczony do adresu loopback i nazw kontenerów testowych. Kopiuje aktualną
wtyczkę do tej instalacji. Prywatny eksport trafia tylko do `/tmp` kontenera CLI,
poza katalogiem HTTP, i zostaje usunięty także przy błędzie. Wszystkie 41 stron
są sprawdzane dla oryginalnego i zmienionego przez SEO źródła: hash pierwszego
banneru, nienaruszone bajty dalszej treści, niezmienione liczniki shortcode i
identyczny wynik kolejnych modułów. Te źródła są podstawiane wyłącznie do pamięci
obiektu WordPressa; pełne treści eksportu nie trafiają do tabel WWW ani raportu.
Fixture HTTP zawiera tylko już publiczny pierwszy banner trzech ofert oraz
syntetyczną dalszą treść. Nie zawiera prywatnych pozostałych sekcji strony.

Przeglądarka sprawdza trzy rzeczywiste trasy ofertowe, szerokości 1440/1920/390/320,
pojedynczy nowy hero bez obu dawnych bannerów, zachowaną semantykę H1, lokalne fonty,
wersaliki, CTA, zdjęcie oraz mierzy faktyczne zakresy tekstu i najdłuższe wyrazy
wszystkich 41 nazw. Osobne próby sprawdzają bezpieczny powrót do dawnego nagłówka
po nieznanej zmianie treści przez filtr, ochronę hasłem, wyłączenie wyglądu oraz
wykluczenie wpisów, innych stron i trybów builder/preview. HTTP z prawdziwym
Rank Math weryfikuje też niezmienione tytuły, opisy i adresy canonical. Porównanie
bazy obejmuje każdy rekord wpisu i metadanych przed i po samych wywołaniach
prezentacji. Liczniki shortcode są testowymi callbackami, więc nie zastępują
kontroli z licencjonowanym Divi. Zdjęcia w Chromium są jawnie oznaczonymi SVG;
zrzuty sprawdzają układ, a nie wygląd rzeczywistych fotografii produkcyjnych.

`offer-hero-results.txt` zawiera końcowy przebieg wersji 3.2.0: 543 kontrole
źródła i renderu, 44 HTTP z Rank Math, 162 Chromium i jedno dokładne porównanie
wszystkich wpisów oraz metadanych po wywołaniach prezentacji.

## Katalog oferty i realizacji — 3.3

Na zachowanej, jednorazowej instalacji testowej uruchom:

```bash
YOONA_TEST_EXPORT_JSON=/prywatna/sciezka/diagnostyka.json YOONA_TEST_BROWSER=1 \
  bash tests/run-collections.sh http://127.0.0.1:PORT NAZWA_KONTENERA_CLI NAZWA_KONTENERA_WWW
```

Skrypt przyjmuje wyłącznie adres loopback i kontenery `yoona-seo-test-*`.
Prywatny eksport znajduje się w `/tmp` kontenera CLI i jest usuwany po zakończeniu,
również przy błędzie. Wszystkie 44 podstrony są sprawdzane w pamięci dla oryginalnych
oraz zmienionych przez SEO sekcji kontaktowych. Sprawdzany jest dokładny wynik
całego renderu poza usuwaną sekcją i pełne liczniki modułów. Od poprawki 3.3.1
zmienione atrybuty sekcji oraz nowe publiczne ścieżki również muszą usuwać stary kontakt. Treści źródłowe nie są drukowane ani zapisywane do tabel WWW.
Osobna próba sprawdza wszystkie 153 źródła realizacji: 150 różnych zdjęć osadzonych
w treści i trzy wpisy bez takiego zdjęcia; eksport nie zawiera metadanych załączników.
Próby MariaDB i PHP porównują gramatykę adresów obrazów, w tym pierwszy atrybut
`src`, cudze domeny, ścieżki z `..` i oba warianty usuwanego zdjęcia kontaktowego.

Publiczne fixture zawierają 41 usług, ich już publiczne bannery i wspólną sekcję
kontaktową oraz syntetyczną pozostałą treść. Powstaje 40 syntetycznych realizacji
z prawdziwymi lokalnymi załącznikami JPEG, naprzemiennie jako zdjęcie wyróżniające
i obraz Divi. Błędne zdjęcia, wpisy chronione, szkice i wpisy prywatne nie mogą wejść
do katalogu. Przeglądarka sprawdza 30 kart na pierwszej stronie i dziesięć unikalnych
na drugiej, porządek daty i ID, wyłącznie zdjęcie i tytuł, trzy kolumny na komputerze,
responsywny układ, równe szerokie marginesy, fonty i rzeczywiste zakresy tekstu.
Widoki obejmują 320, 390, 900, 1440, 1920 i 2560 px. Test porównuje dokładny hash
serwowanego CSS z plikiem wtyczki. Rank Math zachowuje tytuły, opisy i canonical
również na drugiej stronie paginacji. Końcowe porównanie obejmuje wszystkie wpisy
i metadane bez wyjątków. Niedostępne zdjęcia produkcyjne w Chromium zastępują
jawnie opisane SVG; zdjęcia realizacji są prawdziwymi lokalnymi załącznikami.

Końcowe próby zmieniają wyłącznie tymczasowe fixture i przywracają dokładne pola
statusu oraz hasła: szkice, strony prywatne i chronione wypadają z katalogu,
chroniona strona pełnej oferty zachowuje formularz hasła, a chroniona konfiguracja
indeksu wpisów nie dostaje nowego szablonu galerii. Przywrócony zwykły indeks nadal
pokazuje 30 zdjęć. Wyłączenie wyglądu przywraca motyw na obu katalogach i usłudze.

`collections-results.txt` zawiera rzeczywiste wyniki wersji 3.3.0: 16 plików PHP,
182 kontroli przygotowania fixture, 743 prywatnego źródła/renderu, 367 HTTP,
202 kontrole Chromium w sześciu szerokościach oraz 8 końcowych prób widoczności
i routingu. Raport rozdziela te fazy i zapisuje porównania wszystkich wpisów oraz
metadanych. `collections-regression-results.txt` zawiera pełny log bazowego rdzenia
WordPress/Rank Math z tej sesji, a następnie 113 końcowych kontroli strony głównej
w Chromium i dodatkowe porównanie bazy. Starsze raporty pozostają historyczne.


## Usunięcie starego kontaktu na wszystkich podstronach — 3.3.1

Na tej samej odizolowanej instalacji WordPressa:

```bash
bash tests/run-contact-cleanup.sh http://127.0.0.1:PORT NAZWA_KONTENERA_CLI NAZWA_KONTENERA_WWW
```

Test używa wyłącznie syntetycznej treści. Sprawdza publiczne strony i wpisy pod
nowymi adresami, edytowane atrybuty i teksty, shortcode wspólnego komponentu,
bezpośredni hook wyjściowy Divi, inny bieżący wpis Theme Builder, wczesne wykonanie
shortcode i gotowy HTML. Obejmuje miniatury, WebP/AVIF, lazy loading, tło oraz
zagnieżdżone sekcje. Komentarze, skrypty, szablony i cytowane przykłady HTML nie
mogą stanowić dowodu widocznego zdjęcia. Samo zdjęcie, sam nagłówek ani elementy
rozłożone pomiędzy różnymi sekcjami nie mogą usuwać pozostałej treści.

Chromium sprawdza wynikowy DOM przy 320 i 1440 px, brak zdjęcia starego bloku,
zachowanie sąsiednich sekcji, tryby edytora i wyłączenie wyglądu. Ostateczne
porównanie obejmuje wszystkie rekordy wpisów i metadanych bez wyjątków.
Licencjonowany motyw Divi nie jest dołączony; jego publiczny hook jest wywoływany
bezpośrednio, a callbacki shortcode stanowią jawne testowe odpowiedniki.

Nowe raporty: `contact-cleanup-baseline-results.txt` (pełna integracja WordPress/Rank Math),
`contact-cleanup-collections-results.txt` (44 oryginały, SEO, edycje i HTTP)
oraz `contact-cleanup-results.txt` (rozpoznawanie komponentu, Chromium i baza).
Raporty poprzednich wersji pozostają niezmienione.


## Treści usług i realizacji — 3.4

Pomiar istniejącego Site Kit i warunków zgody można sprawdzić bez usług:

```bash
node tests/test-contact-measurement.cjs
```

Na własnej zachowanej instalacji bazowej (`YOONA_TEST_KEEP=1 bash tests/run-wordpress.sh`):

```bash
YOONA_TEST_EXPORT_JSON=/prywatna/sciezka/diagnostyka.json YOONA_TEST_BROWSER=1 \
  bash tests/run-growth.sh http://127.0.0.1:PORT NAZWA_KONTENERA_CLI NAZWA_KONTENERA_WWW
```

Prywatny eksport jest opcjonalny; umożliwia sprawdzenie rzeczywistych 150 źródeł
i dziesięciu stanów po SEO. Runner przenosi go tylko do `/tmp` kontenera CLI
i usuwa również przy błędzie. Źródła są podstawiane do pamięci obiektów WP;
nie trafiają do publicznych tabel i nie są logowane. Publiczne fixture mają
syntetyczną treść, zdjęcia i formularze. Komponent opisów korzysta z publicznych
faktów dołączonych do wtyczki; kontekst i skróty rzeczywistych źródeł weryfikuje
osobna faza CLI.

Faza zapisu sprawdza uprawnienia, nonce, blokadę, sześć stron, pomijanie zajętych
adresów, brak duplikatów, zachowanie istniejącego SEO i cofanie tylko całkowicie
niezmienionych stron. Metadane innej wtyczki także mogą zablokować cofnięcie.
Pierwszy render Rank Math może tworzyć cache zdjęcia OG; jeżeli fixture tego
wymaga, odpowiedzi należy rozgrzać przed ścisłym snapshotem, bez wykluczania
żadnych wierszy lub kluczy.

Przeglądarka porównuje dokładny CSS, wszystkie 47 kategorii, materiały i FAQ,
metadane, formularze, fotografie, równość kontaktów i rzeczywiste zakresy tekstu.
Weryfikuje także, że zdarzenia kontaktowe są intencją, nie potwierdzonym leadem,
i że payload nie zawiera telefonu, e-maila, wartości formularza ani query.
Wariant Site Kit używa istniejącego gtag i zgód CookieYes/WP Consent API;
wariant GTM jest wybierany przy braku gtag, bez podwójnej wysyłki.
Odmowa oraz brak rozpoznanej zgody nie wysyłają ani nie odkładają zdarzeń.
Obrazy lokalne są oznaczone jako testowe. Nie są to zdjęcia modeli SELT.

Raport bieżący to `growth-results.txt`. `growth-baseline-results.txt` zachowuje
wcześniejszy bazowy przebieg 3.3.1 i wyraźnie opisuje swój zakres. Historycznych
raportów innych wydań nie nadpisano. Pełne archiwum instalacyjne ma teraz
37 dozwolonych plików. Sześć nowych stron tworzy osobny przycisk administratora;
sama aktywacja ani wyświetlenie panelu nie wykonują zapisu.


## Galerie i dolne sekcje ofert — 3.4.1

Aktualny test tej warstwy:

```bash
YOONA_TEST_EXPORT_JSON=/prywatna/sciezka/diagnostyka.json bash tests/run-gallery-repair.sh
```

Eksport jest opcjonalny. Runner uruchamia własny WordPress, MariaDB, WP CLI
oraz prawdziwy Rank Math. W razie braku lokalnego źródła Rank Math pobiera jego
przypięty oficjalny commit. Prywatne źródła trafiają wyłącznie do `/tmp` CLI,
są sprawdzane w pamięci i usuwane także po błędzie. Nigdy nie trafiają do tabel
WWW. Jednorazowe zasoby są usuwane przez runner; `YOONA_TEST_KEEP=1` pozostawia
wyłącznie własny stos do diagnostyki. Nie używaj go wobec produkcji.

Regresja sprawdza oryginalnych rodziców galerii, wszystkie dolne moduły,
klasy indeksów CSS i wykonanie callbacków raz. Prywatna faza porównuje pełne
sekcje bajt po bajcie po `do_shortcode`, przed późniejszym dodawaniem atrybutów
obrazów przez WordPressa. Końcowy DOM i układ są osobno badane w Chromium.
Odmienne, ręcznie edytowane wstępy zachowują cały oryginalny wynik. Test wyjątku
sprawdza domknięcie bufora i usunięcie tylko tymczasowych filtrów. Sześć nowych
stron jest sprawdzanych z istniejącym instalatorem i ochroną nowszych edycji.

Publiczne próby używają syntetycznych sekcji oraz jawnie oznaczonych zdjęć.
Callbacki obejmują wszystkie typy modułów z ofert, ale nie są licencjonowanym
Divi. Raport `gallery-repair-results.txt` zachowuje rzeczywisty wynik bieżącej
poprawki oraz oczekiwaną porażkę poprzedniej wersji. Starszy zestaw `growth`
i jego raporty opisują 3.4.0; nie potwierdzały zachowania całego drzewa Divi.
Do regresji układu ofert po 3.4.1 używaj niniejszego zestawu.


## Kompaktowe panele i mobilna góra — 3.4.2

Na nowym, jednorazowym stosie testowym (nie na produkcji):

```bash
YOONA_TEST_KEEP=1 YOONA_GALLERY_PREPARE_ONLY=1 bash tests/run-gallery-repair.sh
```

Runner zapisuje nazwy własnych zasobów i adres w
`/tmp/yoona-gallery-active-environment`. Zastąp poniższe nazwy wyłącznie
wartościami z tego pliku:

```bash
docker cp tests/compact-service.php NAZWA_KONTENERA_WWW:/var/www/html/yoona-tests/compact-service.php
docker exec NAZWA_KONTENERA_WWW chmod a+r /var/www/html/yoona-tests/compact-service.php
docker exec -u 0 NAZWA_KONTENERA_CLI wp --allow-root --path=/var/www/html eval-file /var/www/html/yoona-tests/compact-service.php prepare --skip-themes
docker exec -u 0 NAZWA_KONTENERA_CLI wp --allow-root --path=/var/www/html eval-file /var/www/html/yoona-tests/gallery-repair.php owned --skip-themes
node tests/compact-service.cjs http://127.0.0.1:PORT NAZWA_KONTENERA_CLI
```

Fixture dopuszcza tylko loopback i istniejące 41 syntetycznych stron galerii.
Do nich wstawia publiczne teksty z wtyczki oraz syntetyczny nagłówek i wstęp.
Testowy MU-plugin przez `ReflectionProperty` podmienia rozpoznawane hashe
wyłącznie w pamięci procesu. Nie zmienia plików wtyczki, produkcyjnego manifestu
ani jego zabezpieczeń. Dzięki temu opisy są sprawdzane wewnątrz rzeczywistego
`.yn-content.yn-service-original`, z konkurencyjnym `font-family: !important`.
Flagi i totemy zachowują obraz we wstępie oraz oba warianty tytułu `p`/`h1`.
Prywatny eksport nie jest potrzebny ani wczytywany przez te próby.

Porównanie bazowe podstawia wyłącznie CSS z commita wydania 3.4.1
`f9955dd6c711c422d9e01fff17c07b8ac0bb4e78`; domyślnie nie używa ruchomego
HEAD. Opcjonalne `YOONA_COMPACT_BASELINE_REF` wskazuje inną zgodną bazę.
Chromium sprawdza 11 reprezentatywnych ofert przy 1440 px i wszystkie 47
przy 760, 390 i 320 px. Mierzy dopasowanie tekstu, lokalne fonty, układ 3+2,
2 i 1 kolumny, pojedynczy nagłówek fotograficzny, rozmiary CTA, kolory przycisku,
rodziców galerii i komplet dolnych callbacków. Dodatkowe białe zdjęcie służy
kontroli warstwy przyciemniającej i przeglądowi zrzutu; to nie liczbowy pomiar
kontrastu ani certyfikacja dostępności. Wszystkie wpisy i metadane są
porównywane bez wyjątków po rozgrzaniu własnego cache Rank Math.

Zrzuty oraz `geometry.json` trafiają do `/tmp/yoona-compact-service-screenshots`
(lub `YOONA_TEST_SCREENSHOTS`). Są to oznaczone syntetyczne fotografie
oraz callbacki zastępujące Divi; nie potwierdzają zdjęć produkcyjnych ani
licencjonowanego motywu. Raport: `compact-service-results.txt`.

Po pracy usuń tylko utworzone kontenery CLI/WWW/DB, ich wolumen i sieć wskazane
przez ten przebieg. Nie używaj globalnego `docker prune` ani cudzych zasobów.


## Zestawy komponentów — 3.5.0 i 3.5.1

Poniższe próby obejmują komponenty wprowadzone w 3.5.0; dodatkowe regresje
3.5.1 opisano na końcu. Raporty starszych wersji
pozostają historyczne; samo ich istnienie nie oznacza ponownego wykonania na
aktualnym kodzie. Faktycznie wykonane przebiegi opisuje
[dokument wyników](../docs/WYNIKI-TESTOW.md). Polecenia uruchamiaj z katalogu
repozytorium. Przeglądarkowe zestawy wymagają `playwright` i Chromium, jak wyżej.

### Wspólny, jednorazowy WordPress

Własny jednorazowy stos przygotuj istniejącym runnerem:

```bash
YOONA_TEST_KEEP=1 YOONA_GALLERY_PREPARE_ONLY=1 bash tests/run-gallery-repair.sh
```

Nazwy zasobów i loopback URL odczytaj z `/tmp/yoona-gallery-active-environment`.
Następnie podstaw te wartości w poniższych poleceniach. Skrypty sprawdzają
loopback i własne fixture; nie uruchamiaj ich wobec produkcji. Stos zawiera
aktualną wtyczkę, prawdziwy WordPress i Rank Math oraz 41 syntetycznych ofert.
Przygotowanie poniżej dodaje sześć ofert istniejącym instalatorem, publiczny
indeks wpisów, syntetyczne realizacje i dwie strony firmowe. Cały katalog
`tests` zawiera wyłącznie kod oraz publiczne fixture — bez prywatnego eksportu.

```bash
docker cp tests/. NAZWA_KONTENERA_WWW:/var/www/html/yoona-tests/
docker exec NAZWA_KONTENERA_WWW chmod -R a+rX /var/www/html/yoona-tests
docker exec -u 0 NAZWA_KONTENERA_CLI wp --allow-root --path=/var/www/html eval-file /var/www/html/yoona-tests/gallery-repair.php owned --skip-themes
docker exec -u 0 NAZWA_KONTENERA_CLI wp --allow-root --path=/var/www/html eval-file /var/www/html/yoona-tests/company-pages.php prepare --skip-themes
docker exec -u 0 NAZWA_KONTENERA_CLI wp --allow-root --path=/var/www/html eval-file /var/www/html/yoona-tests/collections35.php prepare --skip-themes
docker exec -u 0 NAZWA_KONTENERA_CLI wp --allow-root --path=/var/www/html eval-file /var/www/html/yoona-tests/premium-projects-browser-fixtures.php --skip-themes
```

Skrypty `prepare` zapisują wyłącznie dane tego stosu. Nie uruchamiaj ich
między `snapshot` i `verify` ani równocześnie z testem sprawdzającym brak
zmian w bazie. Testy zapisujące uruchamiaj kolejno. Po zmianie plików produktu
należy również zsynchronizować źródło wtyczki z własnym kontenerem WWW;
ponowne uruchomienie runnera tworzy świeży stos z aktualnym źródłem.

### O nas i Kontakt

Po wspólnym przygotowaniu:

```bash
docker exec -u 0 NAZWA_KONTENERA_CLI wp --allow-root --path=/var/www/html eval-file /var/www/html/yoona-tests/company-pages.php guards --skip-themes
node tests/company-pages.cjs http://127.0.0.1:PORT NAZWA_KONTENERA_CLI
```

Opcjonalna faza `private` przyjmuje ścieżkę eksportu wyłącznie w `/tmp`
kontenera CLI. Porównuje oryginał i wariant po SEO obu stron w pamięci,
bez zapisu do tabel WWW i bez drukowania źródeł. Po niej usuń prywatny plik,
także w przypadku błędu. Nie umieszczaj eksportu w repo ani katalogu WWW.

```bash
docker cp /prywatna/sciezka/diagnostyka.json NAZWA_KONTENERA_CLI:/tmp/yoona-company-private-source.json
docker exec -u 0 NAZWA_KONTENERA_CLI chmod a+r /tmp/yoona-company-private-source.json
docker exec -u 0 NAZWA_KONTENERA_CLI wp --allow-root --path=/var/www/html eval-file /var/www/html/yoona-tests/company-pages.php private /tmp/yoona-company-private-source.json --skip-themes
docker exec -u 0 NAZWA_KONTENERA_CLI rm -f -- /tmp/yoona-company-private-source.json
```

Ostatnie polecenie sprzątające wykonaj również wtedy, gdy próba zwróci błąd.

Publiczna faza używa syntetycznych stron i jawnego MU-pluginu podmieniającego
wyłącznie hashe w pamięci. Manifest i pliki wtyczki nie są zmieniane. Callbacki
odtwarzają rodziców Divi, jego clearfix, siedem zdjęć, osiem FAQ, karty biur,
mapę, widget opinii i później dodany formularz. Strony mają konkurencyjne
style inline i style nagłówków z `!important`. Nie jest to licencjonowany Divi
ani usługa dostarczania formularzy i opinii na produkcji.

Chromium sprawdza 320, 390, 760, 1024, 1440, 1920 i 2560 px: brak przyciętych
tekstów, lokalne fonty, kontrast kolorów na jednolitych tłach, nagłówek,
zmianę kolumn, przyciski, mapę, FAQ i natywną walidację formularza. Fotografie
są oznaczonymi lokalnymi SVG, a ramka Google Maps ma lokalny dokument testowy.
Test nie potwierdza zdjęć ani działania zewnętrznych usług. Po rozgrzaniu
Rank Math porównywane są wszystkie rekordy wpisów i metadanych bez wyjątków.

Raport: [premium-release35-results.txt](premium-release35-results.txt). Zrzuty i `geometry.json` trafiają do
`/tmp/yoona-company-pages-screenshots` lub `YOONA_TEST_SCREENSHOTS`.

### Pełna oferta, realizacje i ikony

Kontrola geometrii z rzeczywistym CSS i fontami, bez serwera WordPressa:

```bash
node tests/collections35.cjs
```

Kontrola API ikon, głównego zapytania WordPressa i jawnie oznaczonego
zapytania wtórnego natywnego indeksu Divi, a następnie faktycznych tras WWW:

```bash
docker exec -u 0 NAZWA_KONTENERA_CLI wp --allow-root --path=/var/www/html eval-file /var/www/html/yoona-tests/collections35.php query --skip-themes
node tests/collections35-wordpress.cjs http://127.0.0.1:PORT NAZWA_KONTENERA_CLI
```

Zestawy sprawdzają 47 ofert bez paginacji, 24 fotograficzne realizacje na
stronę, kolejne unikalne wyniki i natywne sumy SQL. Układ ma sześć kolumn
od 1400 px, cztery od 1100, trzy od 760, dwie od 480 i jedną poniżej.
Sprawdzane są pełne tytuły, marginesy, pierwszeństwo ładowania zdjęć, ikony
usług i czterech portali społecznościowych oraz pomijanie wpisów niewidocznych
i bez odpowiedniej fotografii. Wariant WordPress porównuje serwowane bajty
CSS z plikiem źródłowym oraz wszystkie wpisy i metadane przed i po oglądaniu.
Zrzuty: `/tmp/yoona35-collections-screenshots` lub `YOONA_TEST_SCREENSHOTS`.
Statyczna próba CSS nie jest testem zapytań WordPressa ani interfejsu Divi.

### Pojedyncze realizacje i zachowanie całego źródła

Publiczne dwie realizacje przygotowuje wspólny krok
`premium-projects-browser-fixtures.php`. Jedna ma rodziców Divi z fotografią,
druga natywną galerię trzech zdjęć. Ich treść jest jawnie syntetyczna.
Uruchom przeglądarkę na tych rzeczywistych trasach WordPressa:

```bash
node tests/premium-projects.cjs http://127.0.0.1:PORT
```

Próba obejmuje 320, 390, 620, 760, 1024, 1440, 1920 i 2560 px, liczbę zdjęć,
rodziców, podpisy, FAQ, linki, przyciski, pełne zakresy tekstów i zgodność
serwowanego `projects.css`. Zrzuty:
`/tmp/yoona-premium-projects35-screenshots` lub `YOONA_TEST_SCREENSHOTS`.

Opcjonalnie wszystkie 150 źródeł realizacji i dziesięć rozpoznanych wariantów
SEO można sprawdzić bez kopiowania eksportu do kontenera. Eksport jest czytany
przez stdin i pozostaje wyłącznie w pamięci procesu CLI:

```bash
docker exec -i -u 0 NAZWA_KONTENERA_CLI wp --allow-root --path=/var/www/html eval-file /var/www/html/yoona-tests/premium-projects.php --skip-themes < /prywatna/sciezka/diagnostyka.json
```

Ta faza porównuje pełny wynik oryginalnych callbacków przed późnymi
atrybutami obrazów WordPressa, wykonanie każdego callbacku raz, wszystkie
fotografie i kolejność, ręczne edycje, nierozpoznane źródło, wyjątek renderera
oraz dokładne rekordy wpisów i metadanych. Sprawdza też rozdzielenie faktów
o pracy od ogólnych wskazówek dla 26 usług. Nie wysyła eksportu do HTTP ani
repozytorium i nie zapisuje jego treści w publicznych tabelach lub raporcie.

### Zapis i przywracanie natywnego Divi

Testy używają prawdziwych API WordPressa i SQL. Tryb `readonly` sprawdza
generowane natywne shortcode, kopiowalne sekcje i moduły, zachowanie pełnych
funkcjonalnych sekcji źródła, dynamiczny indeks realizacji oraz wyłączenia
edytorów. Tryb `mutation` na własnych tymczasowych wpisach sprawdza zapis,
nonce, uprawnienia, KSES, wykrywanie zmienionego źródła, równoległe procesy,
kopie, błędy metadanych, zweryfikowane cofanie i niezależne odzyskiwanie
ręcznych zmian przy wyłączonych rewizjach. Przywraca swoje ustawienia
i usuwa utworzone wpisy i terminy. Nie uruchamiaj go równolegle z innym
testem zapisującym ani ze ścisłym snapshotem przeglądarki.

Skopiuj plik również do `/tmp` CLI: procesy konkurencyjne potrzebują jego
rzeczywistej ścieżki, a nie pseudościeżki `eval-file`:

```bash
docker cp tests/divi-editor.php NAZWA_KONTENERA_CLI:/tmp/yoona-divi-editor.php
docker exec -u 0 NAZWA_KONTENERA_CLI chmod a+r /tmp/yoona-divi-editor.php
docker exec -u 0 NAZWA_KONTENERA_CLI wp --allow-root --path=/var/www/html eval-file /tmp/yoona-divi-editor.php readonly --skip-themes
docker exec -u 0 NAZWA_KONTENERA_CLI wp --allow-root --path=/var/www/html eval-file /tmp/yoona-divi-editor.php mutation /tmp/yoona-divi-editor.php --skip-themes
```

Te próby nie otwierają licencjonowanego Visual Buildera. Potwierdzają
rzeczywiste zapisane źródło i działanie zabezpieczeń WordPressa przy jawnych
callbackach zastępujących Divi. Dla właściciela proces pozostaje ręczny,
osobno dla strony: **YOONA — edycja układu w Divi → Edytuj układ w Divi →
Zapisz układ i edytuj w Divi**. Nie ma migracji przy aktywacji ani masowego
nadpisywania stron. Szczegóły: [instrukcja Divi](../docs/EDYCJA-DIVI.md).

### Slider i przeglądarka zdjęć

Izolowany zestaw korzystający z rzeczywiście wysyłanych plików CSS/JS:

```bash
node tests/showcase-browser.cjs
```

Sprawdza pięć slajdów, stałą wysokość, teksty i przyciski na różnych
szerokościach, pauzę, klawiaturę, ograniczony ruch i działanie bez JavaScript.
Przeglądarka zdjęć ma pojedynczy dialog, obsługę Escape i strzałek, powrót
fokusu, ukryte nazwy plików i kontakt dopasowany do szerokości fotografii.
Test porównuje także zachowanie przy obecności testowego odbiorcy kliknięć
galerii Divi. Jest to jawny lokalny fixture HTML/SVG, nie render WordPressa
ani licencjonowanej galerii Divi. Raport: `showcase-results.txt`; zrzuty:
`/tmp/yoona-showcase-screenshots` lub `YOONA_SHOWCASE_SCREENSHOTS`.

Końcowe połączenie z faktycznymi szablonami WordPressa i Rank Math, po
przygotowaniu powyższego wspólnego stosu:

```bash
node tests/final-smoke35.cjs http://127.0.0.1:PORT
```

Ten test nie tworzy fixture ani nie zmienia opcji. Sprawdza faktycznie
wczytane zasoby względem źródła, pięć slajdów i wszystkie 47 odnośników
ofertowych, ikony, przeglądarkę zdjęć oraz fotografie sześciu nowych usług
w rzeczywistych lokalnych odpowiedziach WordPressa. Rank Math ma pojedyncze
tytuły, opisy, canonical, robots i schema. Zrzuty oraz geometria:
`/tmp/yoona35-final-smoke-screenshots` lub `YOONA_TEST_SCREENSHOTS`.

### Dane SEO, zakres i sprzątanie

Kontrola publicznych plików treści bez HTTP, prywatnego eksportu i zapisu:

```bash
python3 tools/audit_seo_content.py
```

Sprawdza tytuły, opisy, definicje usług i realizacji, wewnętrzne odnośniki
oraz odrębne przypisania zdjęć. Nie jest pomiarem Search Console, szybkości,
indeksacji lub pozycji Google. Dostęp do żywej strony yoona.pl jest
w środowisku zablokowany. Zewnętrzne fotografie w próbach przeglądarkowych
są zastępowane oznaczonymi lokalnymi SVG; nie potwierdzają dostarczenia
zdjęć produkcyjnych. Prawdziwy WordPress i Rank Math nie oznaczają obecności
licencjonowanego Divi — shortcode mają jawne testowe callbacki.

Żaden zestaw nie instaluje nowych wtyczek na stronie właściciela ani nie
zmienia jego kont Analytics. Aktualizacja użytkownika to ZIP, odświeżenie
wyglądu YOONA i **LiteSpeed Cache → Narzędzia → Opróżnianie → Purge All**;
wcześniej zapisanego SEO nie trzeba ponawiać.

Po zakończeniu usuń prywatne pliki z `/tmp` CLI, jeśli były używane, oraz
tylko własne kontenery CLI/WWW/DB, wolumen i sieć podane przez runner.
Nie uruchamiaj globalnego `docker prune` ani czyszczenia cudzych zasobów.


## Regresje O nas, kategorii Edge i realizacji — 3.5.1

Użyj własnego jednorazowego stosu oraz wspólnego przygotowania opisanego
powyżej, z aktualnym źródłem wtyczki i aktualnym katalogiem `tests`.
Skrypty przygotowujące fixture zapisują syntetyczne dane; wykonaj je przed
rozgrzaniem stron i ścisłym porównaniem bazy. Nie uruchamiaj równolegle
przygotowania, testów zapisu ani prób wymagających niezmienionej bazy.
Faktyczne wyniki i liczby kontroli podaje
[WYNIKI-TESTOW.md](../docs/WYNIKI-TESTOW.md), a nie historyczne raporty 3.5.0.

### O nas: prawdziwe ustawienia odstępów i fontów

Zaktualizowany `company-pages.php prepare` odwzorowuje jedną kolumnę wstępu,
tekst z górnym marginesem 20%, paddingi kolumn i tekstu oraz starszy,
bardziej szczegółowy selektor `!important`. Nagłówki mają konkurencyjne
fonty i rozmiary także w zagnieżdżonych linkach, spanach i pogrubieniach.
FAQ ma dwie kolumny po cztery elementy, początkowo otwarte tak jak
w oryginalnej treści. Próba sprawdza zamykanie i ponowne otwieranie,
zamiast narzucać inny stan źródłu.

Oryginalne O nas zawiera dziesięć pustych akapitów. Publiczny fixture
obejmuje jedenaście przypadków: siedem `&nbsp;`, pusty span Worda,
puste formatowanie, pusty akapit oraz dodatkowe `<br>`. Ochrona obejmuje
również pozornie puste komunikaty dynamiczne, kotwice, ikony CSS, SVG,
formularze i nierozpoznane shortcode, zarówno w sekcji rozpoznanej, jak i ręcznie
dodanej. Prywatny eksport nadal służy wyłącznie kontroli w pamięci CLI;
nie trafia do publicznych stron, raportów ani repozytorium.

```bash
docker exec -u 0 NAZWA_KONTENERA_CLI wp --allow-root --path=/var/www/html eval-file /var/www/html/yoona-tests/company-pages.php guards --skip-themes
node tests/company-pages.cjs http://127.0.0.1:PORT NAZWA_KONTENERA_CLI
```

Przeglądarka mierzy nagłówki Archivo 400 oraz ich potomków, Manrope 14 px
w treści, sekcje 24 px na komputerze i 18 px do szerokości 640 px,
zerowy margines i padding dawnego modułu oraz wszystkie zachowane zdjęcia.
Przed pomiarem przewija do zdjęć z leniwym ładowaniem; pusty obszar przed
załadowaniem obrazu nie jest uznawany za poprawny widok.
Kontrolowane porównanie z 3.5.0 odtworzyło dawny górny margines
122,594 px na komputerze i 56 px na telefonie; 3.5.1 daje odpowiednio
0 i 0 px. Zakres oraz wynik tego porównania podaje dokument wyników.

### Kategorie strony głównej: rzeczywisty Edge i dwa arkusze

Komponent ma kompletne reguły w `design.css` i `collections.css`, ze
strzałkami rysowanymi w CSS. Lokalna regresja używa rzeczywistych arkuszy,
fontów, publicznych tytułów oraz SVG wtyczki. Sprawdza normalne wczytanie,
celowo zablokowany `collections.css`, brak reguł kategorii w `design.css`
oraz usunięte komentarze i zagęszczone białe znaki. Ostatni wariant nie
jest testem produkcyjnego optymalizatora LiteSpeed.

```bash
node tests/edge-directory351.cjs
```

Bez dodatkowych zmiennych ten test uruchamia Chromium z identyfikatorem
Edge/Windows. Rzeczywistego Edge wybiera osobna zmienna `YOONA_TEST_EDGE`:

```bash
YOONA_TEST_EDGE=/sciezka/do/zweryfikowanego/msedge node tests/edge-directory351.cjs
YOONA_TEST_EDGE=/sciezka/do/zweryfikowanego/msedge node tests/edge-directory351-wordpress.cjs http://127.0.0.1:PORT
```

Pierwszy skrypt potrzebuje lokalnego PHP do uzyskania SVG. Przy PHP
dostępnym wyłącznie w Dockerze wyeksportuj publiczną mapę
`ścieżka usługi → Yoona_SEO_Icons::service(ścieżka)` do lokalnego JSON
i przekaż jego ścieżkę w `YOONA_TEST_ICON_JSON`. Wariant WordPress czyta
faktyczną stronę główną i zasoby po HTTP, porównuje ich SHA-256 ze źródłem
oraz wymaga `?ver=3.5.1`; nie tworzy fixture, nie zapisuje opcji i nie
zmienia konfiguracji motywu.

Wykonane próby korzystały także z rzeczywistego Microsoft Edge
155.0.4283.62 na Linuksie. Pochodzi z oficjalnego repozytorium Microsoftu;
zweryfikowano podpis metadanych, SHA-256 ich indeksu oraz paczki `.deb`.
Paczka została wyłącznie rozpakowana pod `/tmp`, bez instalacji systemowej
ani uruchamiania skryptów instalatora. Wersję, podpis i skróty dokumentuje
[edge-directory351-results.txt](edge-directory351-results.txt).
Testy nie mają dostępu do profilu Windows Edge właściciela i nie ustalają
zawartości produkcyjnej pamięci cache. Jeżeli oba arkusze są stare,
potrzebna jest aktualizacja i zwykłe czyszczenie cache.

### Realizacje: jednolita typografia i trzy rodzaje galerii

```bash
node tests/premium-projects.cjs http://127.0.0.1:PORT
```

Test sprawdza rzeczywiste lokalne trasy WordPressa dla jawnych syntetycznych
wpisów oraz zgodność serwowanego `projects.css`. Znany opis jest włączany
przez testowy, działający tylko dla tych wpisów wariant HTTP; nierozpoznana
treść zachowuje własny tekst bez dopisywania nieaktualnych faktów.
Dodatkowe przypadki w przeglądarce odwzorowują klasyczną galerię WordPressa,
siatkę Divi, jej dawne pływające elementy i style inline. Nie są to moduły
licencjonowanego Divi.

Weryfikowane są Archivo 400 w nagłówkach, dziedziczenie przez pogrubienia,
Manrope w treści, nieucięte tytuły i marginesy. Galeria blokowa, klasyczna
i siatka Divi mają wspólny układ trzech kolumn powyżej 1100 px, dwóch do
1100 px i jednej do 620 px. Jedna fotografia zachowuje proporcje i pełny
kadr. Osobny przypadek wymaga zachowania sceny slidera Divi jako slidera,
bez nadpisania jej szerokości, marginesu i `display` regułami siatki.
Próby sprawdzają również komplet zdjęć, podpisów, linków, FAQ, pojedynczy
H1 i brak przewijania poziomego. Aktualne zrzuty i geometria trafiają do
`/tmp/yoona-premium-projects351-screenshots` lub `YOONA_TEST_SCREENSHOTS`.

Zestawy firmowe i realizacji pozwalają opcjonalnie uruchomić tę samą próbę
w dostępnej, zweryfikowanej przeglądarce Edge, bez jej instalowania w systemie:

```bash
YOONA_TEST_CHROMIUM=/sciezka/do/zweryfikowanego/msedge node tests/company-pages.cjs http://127.0.0.1:PORT NAZWA_KONTENERA_CLI
YOONA_TEST_CHROMIUM=/sciezka/do/zweryfikowanego/msedge node tests/premium-projects.cjs http://127.0.0.1:PORT
```

Aktualizacja dla właściciela to ZIP 3.5.1, **Narzędzia → Yoona SEO →
Odśwież wygląd**, **LiteSpeed Cache → Purge All** i **Ctrl+F5** w Edge.
Nie ponawia zapisanego SEO i nie nadpisuje ręcznych, autorytatywnych
układów Divi. Wyniki lokalne nie oznaczają wdrożenia na yoona.pl,
uruchomienia licencjonowanego Visual Buildera ani potwierdzenia dostarczenia
produkcyjnych fotografii.

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
całego renderu poza usuwaną sekcją, pełne liczniki modułów i bezpieczne zachowanie
zmienionych sekcji. Treści źródłowe nie są drukowane ani zapisywane do tabel WWW.
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

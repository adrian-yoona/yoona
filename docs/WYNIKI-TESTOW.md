# Wyniki sprawdzenia Yoona — wygląd i SEO 3.1.1

Data: 16 października 2026 r. Testy wykonano na odizolowanym WordPressie, bez połączenia z panelem lub bazą produkcyjną yoona.pl.

## Naprawa zapisu przez panel — wersja 3.1.1

W Chromium odtworzono zgłoszony błąd: ukryte pole `name="action"` zasłaniało właściwość `form.action`. Poprzedni skrypt wysyłał POST pod `/wp-admin/[object%20HTMLInputElement]`, otrzymywał 404 i nie tworzył kopii. Wcześniejsze testy HTTP sprawdzały backend, a atrapa DOM w Node.js miała adres jako zwykły ciąg znaków, więc nie odtwarzała tego zachowania przeglądarki.

Poprawiony skrypt czyta `form.getAttribute('action')`. Nowy test klika właściwy przycisk w rzeczywistym panelu WordPressa z aktywnym Rank Math. Potwierdza **200 pozycji w 20 kolejnych partiach**, poprawny adres każdego żądania, dokładny zapis natywnych tytułów i opisów oraz **51 zmian modułów na trzech dużych stronach Divi**. Sprawdza też wszystkie 20 kopii w historii, rozpoznanie już zapisanych wartości i dokładne cofnięcie przez formularze administratora. Są to jawne dane testowe; pełny eksport właściciela był wcześniej sprawdzony oddzielnym testem opisanym niżej.

Odpowiedzi HTML z kodami **403, 404 i 502** zatrzymują zapis z kodem HTTP, bez wykonywania otrzymanego HTML i bez automatycznego ponawiania. Oddzielna próba przepuszcza zapis pierwszych 10 pozycji, po czym zastępuje jego potwierdzenie błędem 502. Skrypt zatrzymuje się po jednym żądaniu, informuje o niepotwierdzonym stanie partii, a zapisana kopia umożliwia dokładne cofnięcie. Obie atrapy Node.js sprawdzają teraz także zasłoniętą właściwość `form.action`.

Raport bieżącej poprawki: **67 asercji Chromium** w [seo-admin-browser-results.txt](../tests/seo-admin-browser-results.txt). Zaliczono też oba testy kontrolera w Node.js i kontrolę składni dziesięciu aktualnych plików PHP wtyczki oraz nowego fixture. Backend zapisu, podział po 10 pozycji, hashe źródłowych treści i pakiet zmian pozostają bez zmian. Kod CSS, fonty i szablony wyglądu nie były zmieniane w 3.1.1.

## Czytelność i typografia — wersja 3.1

Końcowy test Chromium obejmuje **113 asercji** przy szerokościach **1440, 1920, 390 i 320 px**. Sprawdza rzeczywiste lokalne fonty Archivo Black i Manrope, dopasowanie tekstów i podpisów, wielkie litery usług i pełnej oferty, zachowanie menu oraz siatki realizacji. Kontrola pełnej oferty obejmuje także zagnieżdżone etykiety i konkurencyjne reguły fontu podobne do wygenerowanego CSS Divi.

Przy całkowicie białym obrazie pod tekstem test odczytuje piksele tła za końcem każdego wiersza. Pomarańczowy duży nagłówek ma w tym teście kontrast co najmniej **3,75:1**, opis co najmniej **11,2:1**, a pomarańczowe wyróżnienia na jasnym tle co najmniej **4,5:1**. To celowana kontrola wybranych elementów, nie certyfikacja dostępności całej witryny. Poza białym obrazem używany jest wyraźnie oznaczony testowy SVG; rzeczywiste zdjęcie właściciela pozostaje niedostępne z sieci środowiska.

Końcowy raport: [design-browser-results.txt](../tests/design-browser-results.txt). Liczby w raporcie dotyczą dokładnie serwowanego CSS zgodnego z kodem źródłowym. Zdjęcie firmy nie jest przepisywane w bibliotece; efekt wynika z kadru, filtrów i kolejności warstw CSS. Pakiet tekstów i SEO zachowuje ten sam SHA-256.

Ponownie zaliczono **52 testy rzeczywistego HTTP z Rank Math 1.0.280**, składnię dziesięciu plików PHP wtyczki i ścisłe porównanie wszystkich rekordów wpisów/metadanych po przeglądaniu i przełączaniu wyglądu. Przed stanem początkowym rozgrzano oryginalny motyw i dashboard, aby WordPress utworzył własną nawigację i pusty wpis szybkiego szkicu; porównanie nie pomija żadnych rekordów. [Raport HTTP i zachowania bazy wersji 3.1](../tests/design-premium-http-results.txt).

## Pełna regresja pakietu treści — przebieg wersji 3.0

- **17 testów Pythona**: analiza eksportu, parser audytu i rozpoznanie blokady sieciowej. Nie są to pomiary działającej witryny.
- Kontrola składni wszystkich plików PHP oraz **90 asercji integracyjnych WordPressa**: uprawnienia, nonce, import, kontrola stanu przed zapisem, kopie, konflikty, blokada równoległych wdrożeń i odzyskiwanie po błędzie.
- **59 asercji helpera Divi**: wybór modułów tekstowych i odpowiedzi FAQ, hashe, ochrona HTML, odrzucanie shortcode w nowych tekstach i zachowanie pozostałych bajtów źródła. Obejmują także nawiasy w klasach CSS znalezione w rzeczywistym eksporcie.
- **37 asercji Divi i natywnych pól Rank Math**: wspólny zapis tekstów/metadanych, podgląd, cofanie, zachowanie struktury i wykrywanie już zapisanych wartości.
- **30 asercji rzeczywistego HTTP bez Rank Math i 31 z aktywnym Rank Math**: logowanie, panel, wysłanie JSON, nonce, nieaktualna paczka, limity partii, zapis i cofanie. Wynikowy HTML zawiera właściwy tytuł i pojedynczy opis.
- Osobny test natywnego zapisu: metadane pozostają poprawne w wynikowym HTML po wyłączeniu Yoona SEO, a ponowne włączenie umożliwia dokładne cofnięcie wartości. Wcześniejszy test symulowanych filtrów Yoast nie jest testem instalacji Yoast SEO.
- Wykonanie skryptu panelu w Node.js: **200 pozycji w 20 kolejnych partiach**, ochrona przed podwójnym wysłaniem i zatrzymanie po błędzie. Drugi scenariusz z natywnym FormData sprawdza podział 25 pozycji na 10/10/5, pomijanie wyłączonych pól i zachowanie postępu przy błędzie.
- Kontrola paczki wykonywana jest osobno przy każdym wydaniu: 22 dozwolone pliki, poprawne CRC i zgodność bajt po bajcie z kodem źródłowym. Archiwa `yoona-seo-v3.1.1.zip` i `yoona-seo.zip` zawierają tę samą wersję 3.1.1; wcześniejsze ZIP-y pozostają poprzednimi wydaniami.

## Nowa warstwa wyglądu

- **51 asercji HTTP bez Rank Math i 52 z prawdziwym Rank Math**: nowa strona główna, wspólna oprawa podstron, zachowanie treści i shortcode, stan domyślnie włączony, włączenie/wyłączenie/odświeżenie, nonce, uprawnienia, metody HTTP, style, kanały feed, REST, osadzenia i wyłączenia edytorów. Osobna próba sprawdza pojedynczy tytuł SEO także w motywie bez własnej obsługi `title-tag`.
- **2 asercje ochrony strony głównej hasłem** oraz ochrona treści podstrony hasłem w powyższych scenariuszach.
- Trzy porównania wszystkich rekordów wpisów i metadanych potwierdzają, że przeglądanie i przełączanie wyglądu nie przepisuje treści w bazie. Przed pomiarem renderowany jest oryginalny motyw blokowy, aby zakończyć standardowe utworzenie przez WordPress własnego rekordu nawigacji. Nie wyłączono tego rekordu z porównania.
- Przebieg Chromium wersji 3.0 obejmował **60 asercji** na prawdziwych szablonach, CSS i lokalnych fontach przy szerokościach **1440, 390 i 320 px**, obejmujących menu klawiaturą, H1, geometrię kart realizacji, mieszczące się nagłówki, widoczność zachowanej treści oraz błędy JavaScript. Po pełnym przebiegu PHP/HTTP dodano dokładne sprawdzenie geometrii i ponowiono sam test przeglądarki po poprawkach CSS. Aktualny raport przeglądarki opisuje już wersję 3.1 i 113 sprawdzeń; wcześniejszy raport pełnej integracji zachowuje 37 ówczesnych sprawdzeń przeglądarki.

Zdjęcia w teście przeglądarki są zastąpione jawnie oznaczonymi lokalnymi obrazami SVG. Oceniono układ i zachowanie, nie rzeczywiste fotografie. Przegląd zrzutów wykrył i pozwolił poprawić zbyt wąskie karty realizacji na telefonie, mimo że strona nie miała poziomego przewijania. Końcowy test mierzy szerokość oraz położenie każdej karty, aby wykrywać ten problem.

## Test całej rzeczywistej paczki

Prywatny eksport właściciela odtworzono w oddzielnych tabelach bazy, niedostępnych z testowego serwera WWW. **3529 asercji** obejmuje wszystkie **200 pozycji**, **896 poprawek modułów w 56 elementach**, zapis w 20 partiach, ponowny podgląd bez ponownego zapisu, dostępność wszystkich 20 kopii w panelu oraz dokładne cofnięcie całości.

Surowego eksportu nie umieszczono w repozytorium ani w ZIP-ie. Porównania obejmują zachowanie wszystkich fragmentów Divi poza wskazanymi tekstami oraz obecność i poprzednie wartości zmienianych metadanych.

## Środowisko i odtworzenie

Testowany zestaw: **WordPress 7.1.3, PHP 8.3.35, MariaDB 11.4, Rank Math 1.0.280**. Obrazy testowe są przypięte do digestów w skrypcie. Minimalne zadeklarowane wersje WordPress 6.0 / PHP 7.4 nie były osobno uruchamiane.

Rank Math pochodzi z oficjalnego repozytorium, commit `50fc59587112316ad754ca3c9a26e1282795d49d`. Jego checkout zawierał nieaktualną wygenerowaną mapę klas. W jednorazowej kopii testowej odbudowano ją oficjalnym Composerem, bez sieci, instalowania zależności, skryptów i wtyczek Composera; kod źródłowy i lockfile pozostały bez zmian. Test używa obsługiwanej opcji pominięcia rejestracji konta. Nie jest to test dokładnego archiwum wydania Rank Math z katalogu WordPressa.

Pełny wynik: [wordpress-integration-results.txt](../tests/wordpress-integration-results.txt). Instrukcje odtworzenia i izolacji danych: [tests/README.md](../tests/README.md). Kontenery, wolumen i sieć utworzone przez zakończony przebieg są usuwane; obrazy mogą pozostać do kolejnych testów.

## Paczka do pobrania

Plik: [downloads/yoona-seo-v3.1.1.zip](../downloads/yoona-seo-v3.1.1.zip)

SHA-256 ZIP-a:

```text
0fdb80df7f7087e3fbe0ab1f52414acfeef03e95a364919b7517059077fd1cda
```

SHA-256 dołączonego `content/changes.json`:

```text
e97c45484286479ed153ab88fc7000231e1607b40f1a5d71df51f9c3401c1f85
```

## Granice potwierdzenia

Nie wykonano zdalnego wdrożenia na yoona.pl. Aktualizacja wersji 3 domyślnie włącza nowy wygląd, z możliwością powrotu do motywu w panelu. Nie zapisuje automatycznie pakietu tekstów i metadanych; ich zapis właściciel uruchamia osobno.

Nie uruchomiono licencjonowanego motywu Divi. Zachowanie shortcode sprawdzano parserem WordPressa i testowymi callbackami; nie potwierdza to wyglądu działającego motywu. Po wdrożeniu trzeba sprawdzić widok komputerowy i mobilny, galerie oraz formularze.

Filtr sieciowy środowiska blokował odczyt yoona.pl. Audyt obejmuje pełny otrzymany eksport, bez pomiarów produkcyjnego HTML, indeksacji, szybkości, Core Web Vitals, Search Console lub ruchu. Testy nie dowodzą wzrostu pozycji w Google.

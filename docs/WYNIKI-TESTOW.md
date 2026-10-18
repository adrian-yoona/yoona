# Wyniki sprawdzenia Yoona — wygląd i SEO 3.5.0

Ostatnia aktualizacja: 18 października 2026 r. Testy wykonano na odizolowanym WordPressie, bez połączenia z panelem lub bazą produkcyjną yoona.pl. Starsze sekcje zachowują wyniki poprzednich wydań.

## Widoki firmowe, realizacje, galerie i edycja Divi — 3.5.0

Testy wykonano na własnym, odizolowanym WordPressie 7.1.3, PHP 8.3.35,
MariaDB 11.4, prawdziwym Rank Math 1.0.280 i Chromium 154. Przygotowanie
środowiska ponowiono od zera z przypiętymi obrazami i źródłem Rank Math.
Poniższe liczby opisują różne kontrole; nie są liczbą niezależnych testów ani
pomiarem działającej yoona.pl.

- **O nas i Kontakt:** 58 kontroli oryginalnych i zatwierdzonych po SEO
  źródeł oraz 26 kontroli ochrony ręcznych zmian. Prywatne źródło pozostawało
  wyłącznie w CLI. Sprawdzono zachowanie wszystkich callbacków, zdjęć, FAQ,
  mapy, danych zespołu i opinii oraz brak zmian wpisów i metadanych.
  Końcowy ponowny przebieg Chromium przeszedł **303 kontrole** w 14 widokach
  przy 320–2560 px, na końcowym CSS. Sprawdza także fonty, mierzalny kontrast
  na jednolitym tle, formularz, FAQ, mapę i pełny snapshot wpisów/metadanych.
- **Szczegóły realizacji:** 1581 kontroli dla wszystkich 150 oryginalnych
  źródeł i dziesięciu wariantów po SEO. Pełne rodzicielskie drzewo galerii,
  kolejność zdjęć, formularze i późniejsze edycje pozostają zachowane.
  229 kontroli przeglądarki obejmuje pojedyncze zdjęcie i galerię trzech zdjęć
  w 16 widokach przy 320–2560 px. Trzy źródła bez wystarczających faktów nie
  otrzymały wymyślonego opisu realizacji; zachowują pełną oryginalną treść.
- **Katalogi:** 224 kontrole geometrii na 14 szerokościach oraz 231 kontroli
  rzeczywistego WordPressa na dziesięciu szerokościach. Pełna oferta pokazuje
  wszystkie 47 opublikowanych usług od razu. Indeks ma 24 zdjęcia i tytuły,
  sześć kolumn od 1400 px i natywną paginację. Testowe 59 kwalifikujących się
  projektów tworzy trzy strony; szkice, wpisy prywatne, chronione i bez zdjęć
  nie zapełniają siatki. Końcowe 186 kontroli zapytań i SVG obejmuje również
  publiczne zapytanie dynamicznego modułu Divi oraz odrzucanie niedozwolonych
  parametrów tego zapytania.
- **Slider i podgląd zdjęć:** 234 kontrole rzeczywistych zasobów w izolowanym
  HTML przy pięciu szerokościach 320–2560 px. Badano pięć slajdów, stabilną
  wysokość, klawiaturę, pauzę, ograniczony ruch, fallback bez JavaScript,
  kadry pionowe, pasek kontaktu o szerokości zdjęcia, fokus i brak nazw plików.
  Osobny test końcowy wykonuje te zasoby na rzeczywistych szablonach WordPressa:
  **337 kontroli** pięciu szerokości, 47 ofert, SVG, dwóch galerii i odrębnych
  zdjęć sześciu usług, z pojedynczymi metadanymi prawdziwego Rank Math.
- **Natywna edycja Divi:** 74 kontrole źródła i routingu bez zapisów oraz
  36 kontroli rzeczywistego zapisu w bazie. Obejmują POST, nonce, uprawnienia,
  zmiany źródła po podglądzie, KSES, duplikaty metadanych, częściowe błędy,
  weryfikowane cofnięcie oraz niezależne odzyskiwanie ręcznych zmian przy
  wyłączonych rewizjach. Dwa jednoczesne procesy SQL mają jednego zwycięzcę;
  blokada używa atomowego `INSERT IGNORE`, bez nadpisywania tokenu konkurenta.
  Domyślny wygląd niczego nie migruje. Dopiero osobny przycisk zapisuje
  sekcje, wiersze, kolumny i moduły wybranej strony do `post_content`.

Audyt lokalnych danych potwierdza 200 unikalnych i niepustych tytułów/opisów,
47 poradników usługowych, 150 opisów projektów i poprawne odwołania definicji
(147 usługowych i 449 projektowych). Sześć usług CNC/markiz ma 12 odrębnych
adresów rzeczywistych zdjęć z otrzymanego eksportu. Nie potwierdzono pobrania
ani kadrowania tych zdjęć na produkcji. Skorygowano dwa znalezione problemy:
tylko pierwsze zdjęcie katalogu ma wysoki priorytet; link „Pełna oferta”
w opisie oklejania łodzi jest uwzględniany po sprawdzeniu publikacji strony.

W przeglądzie wtyczek nie znaleziono potrzeby dodawania kolejnego narzędzia.
Rank Math i PRO pozostają razem, Site Kit zachowuje istniejącą integrację,
a skrypt pomiaru i silnik zapisu SEO są bajtowo identyczne z 3.4.2. Nie
zmieniono kont Google ani konfiguracji wtyczek właściciela.

**Ograniczenia:** licencjonowany Divi i jego Visual Builder nie były dostępne.
Natywne shortcode i zachowanie treści sprawdza WordPress z jawnymi callbackami
odpowiedników Divi; nie jest to test interfejsu prawdziwego Visual Buildera.
Zdjęcia w próbach przeglądarkowych są oznaczonymi obrazami lokalnymi, mapa
ma lokalny zastępnik strukturalny, formularz jest syntetyczny. Produkcyjne
połączenie blokuje proxy HTTP 403. Nie zmierzono indeksacji, bieżącego rankingu
ani Core Web Vitals. Nie wgrano wtyczki do panelu yoona.pl.

Raport wydania: [premium-release35-results.txt](../tests/premium-release35-results.txt).
Wszystkie 47 plików w przetestowanej instalacji porównano bajt po bajcie ze
źródłem i gotowym ZIP-em. Wersja 3.5.0, CRC oraz suma SHA-256 są zgodne.
[Instrukcje odtworzenia](../tests/README.md) ·
[Edycja Divi i kopie](EDYCJA-DIVI.md) ·
[Przegląd SEO, zdjęć i wtyczek](PRZEGLAD-SEO-I-WTYCZEK.md).
Poniżej zachowano oddzielnie historyczne wyniki wcześniejszych wydań.

## Kompaktowe panele i mobilny nagłówek — 3.4.2

**2774 kontrole Chromium** obejmują 11 reprezentatywnych ofert przy 1440 px i wszystkie 47 przy 760, 390 oraz 320 px. Sprawdzają rzeczywiste szablony WordPressa i Rank Math, dokładny serwowany CSS, załadowany Manrope 800, małe nagłówki paneli, dopasowanie tekstów i brak poziomego przewijania. Pięć paneli tworzy układ 3+2 na komputerze, dwa słupki przy 760 px i jeden na telefonach. Dolny przycisk wyceny zachowuje ciemny tekst bez podkreślenia mimo konkurencyjnych stylów linków kontenera treści.

Na tych samych syntetycznych stronach porównano CSS z wydania 3.4.1 (commit `f9955dd6c711c422d9e01fff17c07b8ac0bb4e78`) z końcowym 3.4.2. Wysokość siatki Banerów spadła **z 776,75 do 413 px, o 46,8%**; CNC z 832,75 do 437 px, o 47,5%; Flag z 776,75 do 389 px, o 49,9%. Wszystkie teksty pozostają widoczne. To pomiary lokalnego układu przy 1440 px, nie pomiar wdrożonej strony.

Telefon otrzymuje jedno zdjęcie w tle nagłówka i dwa przyciski o wysokości 46 px, bez osobnego wysokiego bloku fotografii pod tekstem. Kontrola obejmuje długie polskie tytuły, semantykę `p`/`h1`, pojedyncze H1 i priorytet zdjęcia. Obejrzano również zrzuty z celowo białym obrazem: gradient utrzymuje ciemne tło napisów. Nie wykonano tu liczbowego pomiaru kontrastu ani certyfikacji dostępności.

Test zachowania obejmuje pełnych rodziców galerii, trzy podlinkowane fotografie i wszystkie dolne moduły CTA, FAQ, social oraz formularz na 41 syntetycznych ofertach. Każdy callback wykonuje się raz. Sześć własnych stron nie powiela treści. Ścisłe porównanie wszystkich rekordów wpisów i metadanych po rozgrzaniu cache potwierdza brak zmian podczas przeglądania. Wszystkie **34 pozostałe pliki wtyczki** są identyczne z 3.4.1; zmieniono tylko CSS, numer wersji w pliku głównym oraz readme. Nie zmieniono rendererów, szablonów, danych, SEO ani skryptu Site Kit.

Przygotowano od zera własny WordPress 7.1.3, PHP 8.3.35, MariaDB 11.4 i Rank Math 1.0.280, wraz z pobraniem przypiętego źródła Rank Math; uruchomiono Chromium 154. Publiczne fixture zawierają syntetyczne nagłówki oraz pełne publiczne teksty usług. Testowy MU-plugin podmienia hashe wyłącznie w pamięci, aby sprawdzać nowy opis wewnątrz rzeczywistego kontenera treści z konkurencyjnym `!important`; pliki i produkcyjne hashe pozostają bez zmian. Prywatny eksport nie jest używany w tej próbie. Licencjonowany Divi i produkcyjne fotografie nie były dostępne. Starsze prywatne kontrole 3.4.1 pozostają opisane poniżej, bez przypisywania ich temu przebiegowi.

Raport: [compact-service-results.txt](../tests/compact-service-results.txt). Instrukcja odtworzenia: [tests/README.md](../tests/README.md). Wszystkie 37 plików serwowanej wtyczki porównano bajt po bajcie z końcowym źródłem oraz ZIP-em; CRC i wersja 3.4.2 są poprawne. Nie instalowano aktualizacji na yoona.pl.

## Galerie i dolne sekcje wszystkich ofert — 3.4.1

Naprawiono błąd 3.4: galerie oraz zdjęcia były wyjmowane z oryginalnych rodziców Divi, a część dolnych modułów znikała. Teraz zachowywane jest całe drzewo sekcji, wierszy i kolumn. Nowe opisy zastępują wyłącznie zatwierdzony tekstowy wstęp. Flagi i totemy zachowują również pełne wprowadzenia ze zdjęciami. Nowy test odtworzył zgłoszony błąd na zamrożonej wersji 3.4.0: galeria nie miała swoich oryginalnych przodków.

**1265 kontroli źródła i renderowania** obejmuje wszystkie 41 ofert przed zapisem SEO i po nim: 82 kompletne wersje. Pełne dolne sekcje, łącznie z indeksami CSS, są porównywane bajt po bajcie po standardowym odkodowaniu shortcode, przed późniejszymi filtrami obrazów WordPressa. Każdy oryginalny callback wykonuje się raz. Sprawdzono bezpieczne zachowanie ręcznie zmienionego wstępu i hero, usunięcie tymczasowych filtrów oraz zamknięcie bufora po wyjątku. Prywatne treści były badane wyłącznie w pamięci CLI, bez zapisania źródła do tabel WWW.

**599 kontroli Chromium** obejmuje wszystkie 41 ofert na komputerze, przykłady przy 390 i 320 px oraz sześć nowych stron przy wszystkich trzech szerokościach. Galerie zachowują sekcje, wiersze i kolumny, linki do zdjęć oraz działający CSS zależny od rodziców. Dolne przyciski, FAQ, social i syntetyczny formularz pozostają w swoich kolumnach. Stary blok ze zdjęciem pokrętła jest nadal usunięty. **52 kontrole** dotyczą istniejącego instalatora, pojedynczego wyświetlenia sześciu nowych stron i ochrony późniejszych edycji. Pełny snapshot wszystkich wpisów i metadanych potwierdza brak zmian podczas przeglądania. Pomiar Site Kit ponownie przeszedł 91 asercji Node VM.

W fazie przygotowania poprawiono metadane syntetycznych stron w pamięci oraz etap porównania HTML: WordPress dodaje atrybuty obrazów już po callbacku Divi. Nie poluzowano kontroli źródła. Test wykrył też standardowe kodowanie literalnych nawiasów w HTML przez WordPressa. Po zatwierdzeniu dokładnego źródła renderer obsługuje wyłącznie jego standardowe odkodowanie, bez ponownego wykonywania shortcode.

Środowisko: rzeczywisty WordPress 7.1.3, PHP 8.3.35, MariaDB 11.4, Rank Math 1.0.280, Chromium 154. Callbacki Divi są jawnymi odpowiednikami testowymi; nie uruchomiono licencjonowanego Divi ani produkcyjnych zdjęć. Zachowane zrzuty zawierają oznaczone obrazy lokalne. Nie wykonano zdalnej instalacji na yoona.pl. Raport: [gallery-repair-results.txt](../tests/gallery-repair-results.txt). Testy 3.4.0 opisane poniżej nie sprawdzały pełnych przodków galerii; samo zachowanie zdjęć nie wystarczało do potwierdzenia układu.

## Treści, kontakty i nowe usługi — 3.4.0

Przygotowano 47 poradników usługowych: 41 istniejących ofert i sześć nowych stron CNC oraz markiz. Nowy szablon prezentuje materiały, zastosowanie, proces, czynniki ceny, checklistę wyceny i FAQ. Wszystkie kategorie są dostępne na głównej. Olsztyn oraz Ostróda mają równorzędny kontakt także przy 320 px.

**50 kontroli przygotowania fixture** i **135 kontroli kontekstu oraz zapisu** obejmują wszystkie oferty, brak automatycznych zapisów podczas aktywacji i odczytu, uprawnienia, blokadę równoległych operacji, błędną historię, kolizje z istniejącymi stronami i duplikatami metadanych, powtórzenie instalacji, cofanie wyłącznie niezmienionych stron i zachowanie późniejszych edycji. Sześć nowych stron ma lokalne tytuły i opisy Rank Math oraz normalną treść działającą bez wtyczki.

**792 kontrole prywatnego źródła** sprawdzają 150 opisów realizacji i dziesięć stanów po SEO. Niezgodna późniejsza treść lub tytuł powodują standardowy widok. Testy parsera zachowują kolejność i komplet zdjęć we wszystkich 160 wariantach oraz całe zagnieżdżone galerie, natywne obrazy i formularze Divi. Pełne źródła występują tylko w pamięci CLI; nie trafiają do tabel obsługiwanych przez WWW ani do raportu.

**637 kontroli Chromium** sprawdza rzeczywiste szablony WordPressa i Rank Math przy 1440, 390 i 320 px: wszystkie 47 usług na komputerze, długie tytuły na telefonie, pojedyncze H1, metadane, 47 linków głównej, osiem kart zdjęciowych, 39 pozostałych kategorii, równe kontakty w nagłówku/stopce/Kontakcie, fotografie i formularze, pełną szerokość galerii oraz brak pustej połowy ostatniego rzędu paneli. Dodatkowe **91 asercji Node VM** wykonuje rzeczywisty skrypt pomiaru. Osobne kontrole Chromium sprawdzają kliknięcia kontaktowe przez istniejące gtag Site Kit albo wariant GTM, bez podwójnej wysyłki i danych formularzy, z odmową oraz brakiem zgody, tryby edytora, wyłączenie wyglądu oraz POST i nonce instalatora. Pełne porównanie wpisów i metadanych sprawdza brak zmian podczas przeglądania.

Test wykrył przycinanie słowa „gastronomicznych” w H2 przy 320 px. Zmniejszono minimum nagłówka oraz dodano bezpieczne łamanie długich słów. Oddzielnie skorygowano próbę testową etykiet, która porównywała tekst po wizualnym uppercase, oraz uprawnienia lokalnego pliku CSS po ręcznym kopiowaniu do kontenera. Nie wyłączano kontroli dopasowania tekstu, źródłowego CSS ani ochrony bazy.

Środowisko: WordPress 7.1.3, PHP 8.3.35, MariaDB 11.4, Rank Math 1.0.280 i Chromium 154. Licencjonowany Divi nie jest zainstalowany; jego shortcode zastępują jawne callbacki testowe. Fotografie produkcyjne pozostają niedostępne, więc przegląd geometrii używa oznaczonych obrazów lokalnych. Raport bieżący: [growth-results.txt](../tests/growth-results.txt). [growth-baseline-results.txt](../tests/growth-baseline-results.txt) dotyczy poprzedniej 3.3.1, użytej do przygotowania izolowanej instalacji; nie jest przedstawiany jako pełny przebieg 3.4.

Ponownie przeszło 17 testów audytu Python i oba scenariusze rzeczywistego skryptu zapisu partiami w Node.js. Silnik zapisu, helper Divi oraz `content/changes.json` zachowują identyczne bajty; w kontrolerze admina dodano tylko panel nowych usług. Lista paczki obejmuje 37 plików; CRC, wersję i zgodność wszystkich plików ze źródłem sprawdza narzędzie pakowania.

Końcowa [regresja strony głównej](../tests/growth-home-regression-results.txt) zaliczyła **113 kontroli Chromium**, obejmujących fonty, kontrast i rzeczywiste zakresy tekstu. Po niej ponownie porównano wszystkie wpisy i metadane bez wyłączeń — bez zmian. Wszystkie 37 plików wtyczki serwowanych przez testowy WordPress jest identyczne bajt po bajcie ze źródłami końcowej paczki.

Analiza Google dotyczy dostarczonych eksportów, nie uprawnień do kont. Szczegółowe liczby i raporty pozostają poza repozytorium. Nie wykonano zmian w Google, pomiaru produkcyjnych Core Web Vitals, sprawdzenia indeksacji ani aktualnych konkurentów. Proxy blokuje publiczne odczyty, a zrzut cyber_Folks nie został dostarczony.

## Usunięcie starego kontaktu ze wszystkich podstron — 3.3.1

Poprawka zastępuje zależność od 44 adresów i dokładnych hashy rozpoznaniem samego komponentu: charakterystycznego zdjęcia oraz treści kontaktowej. Testy obejmują WordPress `do_shortcode_tag`, bezpośredni filtr Divi `et_module_shortcode_output` oraz gotowe sekcje HTML. Liczniki modułów pozostają zachowane, a usuwany jest tylko kompletny blok; otaczający HTML pozostaje identyczny.

Ponowiono pełny bazowy zestaw WordPress/Rank Math. **835 kontroli źródła i renderowania** obejmuje wszystkie 44 podstrony, w tym `/beczki-reklamowe/`, edycje sekcji, nowe adresy, wpisy i zachowanie zarejestrowanych filtrów po wyjątku. **367 kontroli HTTP** potwierdza także karty usług, 30 realizacji i natywne metadane Rank Math. **73 nowe kontrole syntetyczne** sprawdzają zmienione teksty, wspólne shortcode, Theme Builder, miniatury, WebP/AVIF, lazy loading, tła, zagnieżdżenia oraz precyzyjne granice HTML. Komentarze, skrypty i przykłady szablonów nie mogą powodować usuwania niezwiązanego bloku.

**47 kontroli Chromium przy 320 i 1440 px** potwierdza brak całego bloku i jego zdjęcia w DOM oraz zachowanie sąsiednich sekcji, pozostałych kontaktów, trybów edytora i wyłączenia wyglądu. Testy korzystają z prawdziwego WordPressa i Rank Math; callbacki Divi są jawnymi odpowiednikami testowymi, a licencjonowany motyw nadal nie jest zainstalowany.

Pierwszy pełny test przeglądarki wykrył dodatkowy rekord `rank_math_og_content_image` na nowym fixture z gotowym HTML. To pamięć podręczna zdjęcia podglądu linku tworzona przy pierwszym renderowaniu przez Rank Math. Runner otwiera teraz wszystkie nowe fixture przed wykonaniem ścisłej kopii porównawczej. Porównanie nadal obejmuje wszystkie wpisy i metadane bez wyjątków. Raport zachowuje nieudany przebieg i diagnozę; szczegóły wcześniejszej nieodtworzonej różnicy CLI opisano w raporcie.

Bieżące dowody: [bazowa integracja](../tests/contact-cleanup-baseline-results.txt), [44 podstrony i katalogi](../tests/contact-cleanup-collections-results.txt), [rozpoznawanie, Chromium i baza](../tests/contact-cleanup-results.txt). Pakiet SEO, jego kontroler, silnik zapisu, CSS i zapytania galerii zachowują identyczne bajty względem 3.3.0. ZIP zawiera te same 28 dozwolonych plików, sprawdzonych bajt po bajcie i przez CRC. Nie wykonano zdalnej instalacji na yoona.pl.

## Poprzednie wydanie: katalog usług, galeria i stary kontakt — wersja 3.3.0

**743 kontrole źródła i renderowania** obejmują pomijanie powtarzanego bloku kontaktowego na wszystkich **44 rozpoznanych podstronach**, w oryginalnym źródle i po poprawkach SEO. Kontrola obejmuje 66 znanych hashy sekcji, dokładne zachowanie pozostałego wynikowego HTML i liczników modułów, bezpieczny fallback po edycji oraz usunięcie tymczasowego filtra po wyjątku. Pełne źródła są podstawiane wyłącznie do pamięci procesu CLI; nie trafiają do tabel WWW ani raportu.

Weryfikacja fotografii obejmuje wszystkie **153 źródłowe realizacje**: 150 zawiera własne zdjęcie, trzy wymagają zdjęcia wyróżniającego skonfigurowanego na witrynie. Eksport nie zawierał tych ustawień mediów. Zapytanie pomija rzeczywiście pozbawione zdjęcia wpisy przed obliczeniem liczby stron. Sprawdzana jest zgodność reguły SQL z parserem obrazów, w tym zdjęcia wyróżniające, obrazy Gutenberg i Divi, niepoprawne adresy, ścieżki nadrzędne, brak mediów i oba warianty starej fotografii kontaktowej.

**367 kontroli HTTP** z aktywnym Rank Math sprawdza katalog 41 usług, po 30 realizacji, kolejne strony bez powtórzeń, tytuły, opisy i canonical. **202 asercje Chromium** mierzą siatki, rzeczywiste zakresy tekstu, fonty, odstępy i szerokość marginesów przy **320, 390, 900, 1440, 1920 i 2560 px**. Karty realizacji zawierają wyłącznie zdjęcie i tytuł. Oddzielne próby obejmują ochronę hasłem, brak zmian w trybach edytora, wyłączenie wyglądu i zachowanie pozostałych archiwów oraz RSS. Ścisłe porównanie wszystkich wpisów i metadanych potwierdza brak zmian treści podczas przeglądania.

Dodatkowa kontrola wykryła wybór szablonu galerii przy chronionej stronie indeksu wpisów, dla której zapytanie galerii było wyłączone. Wybór szablonu i szerokiej oprawy zależy teraz również od flagi głównego zapytania galerii. Końcowe **8 asercji** sprawdza ten fallback, ochronę katalogu usług, wykluczenie niepublicznych usług oraz normalne 30 kart po przywróceniu ustawień. Zaktualizowany test strony głównej zaliczył **113 kontroli**; dawny przypadek treści Pełnej Oferty sprawdza teraz zamówione karty usług. Wynik rdzenia WordPress/Rank Math i tej regresji: [collections-regression-results.txt](../tests/collections-regression-results.txt).

Raport bieżącego przebiegu: [collections-results.txt](../tests/collections-results.txt). Zdjęcia w Chromium są jawnie oznaczonymi lokalnymi SVG; potwierdzają geometrię układu, a nie aktualny stan fotografii produkcyjnych. Próba pobrania zdjęcia z yoona.pl w tej maszynie nadal kończyła się blokadą proxy CONNECT 403. Licencjonowany Divi nie jest zainstalowany; numerację jego modułów sprawdzają testowe callbacki.

Kontroler zapisu, silnik SEO, helper zmian tekstów i `content/changes.json` zachowują identyczne bajty względem wersji 3.2. Wydanie 3.3 zawiera **28 plików** z zamkniętej listy. Wcześniejsze wyniki pozostają osobnymi raportami; nie nadpisano ich wynikami tego przebiegu.

## Nagłówki podstron ofertowych — wersja 3.2

**543 asercje rzeczywistego WordPressa** sprawdzają wszystkie 41 rozpoznanych ofert, zarówno źródło z eksportu, jak i wariant po poprawkach SEO. Obejmują dokładne hashe i bajty treści po pierwszym banerze, renderowanie 82 wariantów z zachowaniem liczników sekcji/modułów, wykluczenie stron z hasłem, szkiców, stron prywatnych i nieobsługiwanych adresów. Zmieniony baner lub dodatkowa transformacja treści powodują zachowanie poprzedniego widoku. Sprawdzono też usunięcie tymczasowego filtra po wyjątku callbacka.

Kontrola wykryła zmianę zwykłego myślnika na półpauzę przez standardowy `wptexturize()` na jednej ofercie. Rozpoznanie nadal wymaga zgodnej sumy kontrolnej źródła; dopiero po nim wyliczany jest dodatkowy hash znanej transformacji WordPressa. Nie wprowadzono dowolnej normalizacji HTML ani pomijania kontroli źródła.

**44 kontrole HTTP** sprawdzają wynikowe nagłówki i pojedyncze metadane właściwego Rank Math. **162 asercje Chromium** obejmują **1440, 1920, 390 i 320 px**, trzy rzeczywiste trasy ofertowe z jawną syntetyczną treścią poniżej banera oraz dopasowanie wszystkich 41 nazw usług. Mierzona jest nie tylko szerokość elementów, lecz także prostokąty tekstu i najdłuższy niepodzielony wyraz. Dzięki temu wykryto i poprawiono łamanie słów „wielkoformatowa” i „gastronomicznych”, które nie powodowało poziomego przewijania. Sprawdzane są lokalne fonty, nazwy wielkimi literami, CTA, fotografia, zachowanie semantyki H1, fallback oraz wyłączenie wyglądu. Dokładne porównanie wszystkich rekordów wpisów i metadanych potwierdza brak zapisów podczas przeglądania. Składnia wszystkich **12 plików PHP wtyczki** jest poprawna.

Raport bieżącej wersji: [offer-hero-results.txt](../tests/offer-hero-results.txt). Pełne prywatne korpusy ofert są analizowane poza tabelami obsługiwanymi przez WWW; do lokalnych tras trafia wyłącznie dotychczasowy publiczny baner i jawnie syntetyczna dalsza treść. Plik eksportu pozostaje poza katalogiem HTTP i repozytorium. Zdjęcia w Chromium są oznaczonymi obrazami SVG; oceniono układ, nie dostępność fotografii na produkcji. Model callbacków potwierdza zachowanie liczników, ale nie zastępuje licencjonowanego Divi.

Pakiet `content/changes.json` i kontroler zapisu SEO z 3.1.1 zachowują identyczne bajty. Wydanie dodaje dwa pliki prezentacji; ZIP zawiera **24 pliki**, poprawne CRC i dokładny kod źródłowy wersji 3.2.0.

## Naprawa zapisu przez panel — wersja 3.1.1

W Chromium odtworzono zgłoszony błąd: ukryte pole `name="action"` zasłaniało właściwość `form.action`. Poprzedni skrypt wysyłał POST pod `/wp-admin/[object%20HTMLInputElement]`, otrzymywał 404 i nie tworzył kopii. Wcześniejsze testy HTTP sprawdzały backend, a atrapa DOM w Node.js miała adres jako zwykły ciąg znaków, więc nie odtwarzała tego zachowania przeglądarki.

Poprawiony skrypt czyta `form.getAttribute('action')`. Nowy test klika właściwy przycisk w rzeczywistym panelu WordPressa z aktywnym Rank Math. Potwierdza **200 pozycji w 20 kolejnych partiach**, poprawny adres każdego żądania, dokładny zapis natywnych tytułów i opisów oraz **51 zmian modułów na trzech dużych stronach Divi**. Sprawdza też wszystkie 20 kopii w historii, rozpoznanie już zapisanych wartości i dokładne cofnięcie przez formularze administratora. Są to jawne dane testowe; pełny eksport właściciela był wcześniej sprawdzony oddzielnym testem opisanym niżej.

Odpowiedzi HTML z kodami **403, 404 i 502** zatrzymują zapis z kodem HTTP, bez wykonywania otrzymanego HTML i bez automatycznego ponawiania. Oddzielna próba przepuszcza zapis pierwszych 10 pozycji, po czym zastępuje jego potwierdzenie błędem 502. Skrypt zatrzymuje się po jednym żądaniu, informuje o niepotwierdzonym stanie partii, a zapisana kopia umożliwia dokładne cofnięcie. Obie atrapy Node.js sprawdzają teraz także zasłoniętą właściwość `form.action`.

Raport poprawki 3.1.1: **67 asercji Chromium** w [seo-admin-browser-results.txt](../tests/seo-admin-browser-results.txt). Zaliczono też oba testy kontrolera w Node.js i kontrolę składni dziesięciu ówczesnych plików PHP wtyczki oraz nowego fixture. Backend zapisu, podział po 10 pozycji, hashe źródłowych treści i pakiet zmian pozostają bez zmian. Kod CSS, fonty i szablony wyglądu nie były zmieniane w 3.1.1.

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
- Kontrola paczki wykonywana jest osobno przy każdym wydaniu: poprawne CRC i zgodność bajt po bajcie z kodem źródłowym. Wersje 3.1 i 3.1.1 zawierają 22 dozwolone pliki, 3.2 — 24, a 3.3 — 28. Ogólny plik `yoona-seo.zip` wskazuje aktualne wydanie; poprzednie ZIP-y zachowano.

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

Plik: [downloads/yoona-seo-v3.5.0.zip](../downloads/yoona-seo-v3.5.0.zip)

SHA-256 ZIP-a:

```text
a8c7ebbf04ef1865237430e62859477e7da9c03413e2970b135636734aa7c3df
```

SHA-256 dołączonego `content/changes.json`:

```text
e97c45484286479ed153ab88fc7000231e1607b40f1a5d71df51f9c3401c1f85
```

## Granice potwierdzenia

Nie wykonano zdalnego wdrożenia na yoona.pl. Aktualizacja wersji 3 domyślnie włącza nowy wygląd, z możliwością powrotu do motywu w panelu. Nie zapisuje automatycznie pakietu tekstów i metadanych; ich zapis właściciel uruchamia osobno.

Nie uruchomiono licencjonowanego motywu Divi. Zachowanie shortcode sprawdzano parserem WordPressa i testowymi callbackami; nie potwierdza to wyglądu działającego motywu. Po wdrożeniu trzeba sprawdzić widok komputerowy i mobilny, galerie oraz formularze.

Filtr sieciowy środowiska blokował odczyt yoona.pl. Audyt obejmuje otrzymany eksport WordPressa oraz analizę dostarczonych raportów Search Console, Analytics i zasięgu reklam. Nie wykonano odczytu produkcyjnego HTML, pomiarów indeksacji, szybkości i Core Web Vitals ani bezpośredniego połączenia z kontami Google. Testy nie dowodzą wzrostu pozycji w Google.

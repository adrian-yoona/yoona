# Przebudowa wyglądu Yoona w wersji 3

Wersja 3 realizuje brakujący zakres wizualny, wskazany przez właściciela w załączonym obrazie. Poprzednia wersja zmieniała treści i metadane, bez przebudowy szaty graficznej.

## Układ według wzoru

- Jasny nagłówek z marką YOONA, nawigacją i pomarańczowym przejściem do wyceny.
- Ciemny hero ze zdjęciem witryny, dużym hasłem „Robimy marki widoczne” i dwoma przejściami.
- Jasna sekcja ośmiu zdjęciowych kart usług: banery, beczki, neony, litery 3D, oklejanie, markizy, windery i reklama dla gastronomii.
- Ciemny pas procesu: od rozmowy i projektu przez produkcję do montażu.
- Siatka istniejących realizacji, pomarańczowe wezwanie do kontaktu, jasny blok o firmie i ciemne zamknięcie z kontaktem i stopką.
- Wspólna oprawa nawigacji, typografii i stopki na podstronach. Układ dostosowuje się do telefonu; menu nie wymaga JavaScript.

Kolory: pomarańcz i czerń z ciepłym jasnym tłem. Nagłówki używają lokalnego Archivo Black, z polskimi znakami i dołączoną licencją SIL OFL. Strona nie pobiera fontów z Google.

Nie odwzorowano panelu administracyjnego, paska wskaźników SEO ani przypadkowych pasków przeglądarki widocznych na zdjęciu referencyjnym. Nie dodano niepotwierdzonych liczb „25+” lub „1000+”. Dane kontaktowe, zdjęcia i odnośniki pochodzą z eksportu istniejącej witryny. Nie dopisano nowego formularza, który udawałby działającą wysyłkę; wycena prowadzi do dotychczasowego kontaktu.

## Mechanizm i cofanie

Wygląd jest domyślnie włączoną warstwą szablonów wtyczki. Samo włączenie nie zapisuje nowego układu do `post_content`, nie usuwa modułów Divi i nie zmienia adresów. Strona główna ma własną kompozycję, a standardowa treść podstron jest przetwarzana przez WordPress. Od wersji 3.5.0 administrator może osobno zapisać wybraną stronę do natywnych modułów Divi, zgodnie z [instrukcją edycji](EDYCJA-DIVI.md). Bez tej operacji Visual Builder edytuje dotychczasowe zapisane źródło; nie edytuje kompozycji generowanej przez szablon.

Panel **Narzędzia → Yoona SEO** pokazuje stan wyglądu, przełącznik powrotu i odświeżenie cache. Wyłączenie wyglądu lub dezaktywacja wtyczki przywraca szablony motywu. Wcześniej zapisane teksty i metadane Rank Math pozostają i mają osobne kopie w historii. Ręcznie zapisane układy Divi pozostają w treści i mają własną kopię do przywrócenia; przełącznik wyglądu nie cofa takiego zapisu.

Włączenie wyglądu nie uruchamia zapisu pakietu 200 pozycji. Panel osobno podaje liczbę wartości zgodnych z pakietem, gotowych do zapisu i zablokowanych. Nie wolno uznawać samej instalacji za potwierdzenie zapisu SEO.

## Sprawdzenie i ograniczenia

Wyniki rzeczywiście wykonanych testów podaje [dokument testów](WYNIKI-TESTOW.md). Warstwa zachowuje `wp_head`, `wp_body_open` i `wp_footer`, dzięki którym działają standardowe zasoby i tagi Rank Math. Nie dodaje drugiego generatora SEO.

Adresy zdjęć wybrano z istniejącej treści witryny. Filtr sieciowy uniemożliwił pobranie ich w środowisku, dlatego nie potwierdzono aktualnych plików ani identycznego kadru ze zdjęciem referencyjnym. Test układu z zastępczymi obrazami sprawdza geometrię, nie fotografię produkcyjną.

Licencjonowany motyw Divi i dodatki do niego nie są dołączone do testów. Po aktualizacji właściciel powinien sprawdzić stronę główną, typową usługę, realizację i kontakt na komputerze oraz telefonie, zwłaszcza galerie, formularze i przyciski. Cache hostingu lub CDN może wymagać czyszczenia w swoim panelu. Nie wykonano zdalnego wdrożenia na yoona.pl ani pomiaru szybkości produkcji.

## Dopracowanie 3.1 według uwag właściciela

Poprawiono kolejność warstw nagłówka, tak aby gradient rzeczywiście przykrywał zdjęcie i znajdował się pod tekstem. Sam plik fotografii firmy pozostaje w bibliotece: kadrowanie, stonowanie barw i przyciemnienie są odwracalną prezentacją CSS. Nie zastąpiono rzeczywistego budynku wygenerowanym obrazem.

Dodano lokalny Manrope Variable do opisów i interfejsu z polskimi znakami, licencją OFL i pochodzeniem. Archivo Black zachowano dla dużych nagłówków, z mniej ujemnym odstępem liter. Zwiększono opisy, nawigację, przyciski i podpisy. Nazwy kart usług, pasek możliwości, oferta w stopce i nagłówki Pełnej Oferty są wyświetlane wielkimi literami; akapity pozostają czytelne w zwykłej pisowni. Treści w bazie i pakiet SEO pozostają bez zmian.

## Nagłówki podstron ofertowych — 3.2

41 rozpoznanych podstron usługowych otrzymuje jeden nagłówek w miejsce czarnego bloku tytułu i osobnego banera Divi. Czarna część tekstowa zawiera nawigację, nazwę usługi wielkimi literami, opis właściwy dla usługi oraz przejścia do wyceny i zakresu poniżej. Duża fotografia zajmuje prawą część; na telefonie występuje pod tekstem. Fonty pozostają lokalne, a kontrast tekstu nie zależy od zdjęcia.

Zdjęcia pochodzą z dotychczasowych banerów. Krótkie opisy pochodzą z opracowanej paczki tekstów. Kontrola obejmuje dokładny hash pierwszej sekcji w eksporcie oraz jej wariantu po SEO, jeśli został zmieniony. Nowy nagłówek pojawia się tylko wtedy, gdy oryginalny baner został jednoznacznie rozpoznany i jego wynik udało się zastąpić. Późniejsze edycje banera powodują powrót do dotychczasowego widoku. Strona główna, Pełna Oferta, O nas, Kontakt, prywatność i wpisy realizacji nie należą do tej listy.

Cała źródłowa treść nadal przechodzi przez WordPress i callbacki Divi. Wynik znanego banera jest pomijany dopiero po jego renderowaniu, aby zachować numerację modułów i odpowiadające jej style. Dalsza oferta, galerie, linki i formularze nie są wycinane. Nowy nagłówek nie dodaje H1, jeśli dalsza treść już go zawiera; nie zmienia istniejących nagłówków w bazie.

Aktualizacja zachowuje naprawę zapisu SEO z 3.1.1. Do zmiany wyglądu wystarczy aktualizacja wtyczki i odświeżenie cache; nie trzeba ponawiać już zapisanych poprawek SEO.

## Oferta, realizacje i usunięcie starego kontaktu — 3.3

Pełna Oferta ma własną listę 41 publicznych podstron usługowych. Pierwsze osiem kart odpowiada zdjęciom i kolejności ze strony głównej, a dalsze korzystają ze zdjęć dotychczasowych nagłówków. Widoczne są zdjęcie i nazwa usługi; cała karta prowadzi do podstrony. Strony niepubliczne i chronione hasłem nie są pokazywane.

Nasze realizacje korzystają z głównego zapytania wpisów WordPressa. Na każdej stronie jest do 30 fotografii z tytułami, w trzech kolumnach na komputerze, dwóch na tablecie i jednej na telefonie. Nie ma opisów, dat, kategorii ani dodatkowych przycisków na kartach. Natywna paginacja prowadzi do dalszych prac. Zapytanie pomija wpisy bez zdjęcia, szkice i wpisy chronione hasłem; zachowuje kolejność od najnowszych z rozstrzygnięciem identycznych dat przez ID.

Oba widoki wykorzystują całą dostępną szerokość z marginesami od 20 do 72 px i równymi odstępami. Zdjęcia mają stałe proporcje i spokojne powiększenie przy najechaniu. Fonty są lokalne; zdjęcia realizacji pochodzą ze zdjęcia wyróżniającego lub pierwszego odpowiedniego obrazu w treści.

Rozpoznany blok Masz pytanie? Kontakt z fotografią czarnego pokrętła jest pomijany na 44 podstronach. Kontrola obejmuje 66 dokładnych hashy sekcji oryginalnych i po SEO. Sekcja nadal przechodzi przez callbacki Divi, aby zachować numerację pozostałych modułów i ich style; usuwany jest tylko jej wynik. Źródło strony i metadane pozostają w bazie. Nowa stopka z kontaktem pozostaje dostępna.

## Poprawka usuwania starego kontaktu — 3.3.1

Mechanizm 3.3 był zbyt wąski: wymagał jednej z 44 ścieżek oraz dokładnego hasha całej sekcji. Dlatego sekcja po edycji lub pod nowym adresem pozostawała widoczna. W 3.3.1 rozpoznawana jest para: charakterystyczny plik zdjęcia pokrętła oraz treść kontaktowa. Działa na wszystkich publicznych podstronach i wpisach, także po zmianie tekstów lub ustawień Divi, bez zależności od listy adresów.

Obsługiwane są standardowe shortcode WordPressa, bezpośredni filtr wyjściowy Divi oraz gotowe sekcje HTML w treści. Usuwana jest kompletna najbliższa sekcja et_pb_section, bez ponownego serializowania pozostałego HTML i bez przesuwania liczników modułów. Komponent jest rozpoznawany również przy miniaturach WordPressa i zoptymalizowanych wariantach pliku zdjęcia. Sam nagłówek kontaktowy albo samo zdjęcie nie wystarczają do usunięcia sekcji. Tryby edytorów, podglądy i treści niepubliczne zachowują poprzednie działanie.

Aktualizacja nie zmienia źródeł stron, metadanych Rank Math, galerii ani pakietu SEO. Nowa stopka i podstrona Kontakt pozostają dostępne. Po zastąpieniu wtyczki należy odświeżyć wygląd i pamięć podręczną; ponowne wgrywanie SEO nie jest potrzebne.


## Treści i widoczność obu lokalizacji — 3.4

Nowy widok obejmuje 41 ofert, 150 rozpoznanych realizacji oraz sześć dodatkowych
usług CNC i markiz po osobnym kliknięciu administratora. Opisy ofert prezentuje
wtyczka z uporządkowanego zestawu treści; zapisane Divi pozostaje w bazie.
Wszystkie callbacki oryginalnego źródła wykonują się raz, a z końcowego HTML
zachowywane są pełne, zbalansowane kontenery zdjęć, galerii i formularzy. Dzięki
temu działają późne atrybuty obrazów i nie powstają duplikaty dwóch hooków Divi.

Opisy realizacji wymagają zgodności całego źródła z rozpoznanym skrótem. Przy
nowej edycji pozostaje zwykła treść. Nowe strony mają normalną treść WordPressa
i natywne SEO; ich późniejsza edycja również powoduje standardowy widok.
Cofanie obejmuje wyłącznie całkowicie niezmienione, utworzone strony.

Główna pokazuje osiem zdjęciowych kart oraz wszystkie pozostałe kategorie bez
powtórzeń i ukrywania w akordeonie. Menu ma jasny link Pełna oferta. Równe
kontakty obu miast są w nagłówku, stopce oraz na stronie Kontakt. Mniejsze
nagłówki, odstępy i pełna szerokość ostatniego panelu ograniczają pustą przestrzeń.
Nie usunięto formularza ani mapy z oryginalnego kontaktu.

Pomiar przekazuje intencje kontaktowe przez istniejące gtag Site Kit po
rozpoznanej zgodzie CookieYes/WP Consent API; nie ładuje drugiego tagu ani
nie zmienia konfiguracji kont. Wariant GTM jest osobną ścieżką przy braku gtag. Szczegóły w GOOGLE-POMIAR-I-ADS.md.


## Naprawa galerii i dolnych sekcji ofert — 3.4.1

W 3.4 same moduły mediów były przenoszone do nowej siatki. Utrata ich rodziców
Divi zepsuła układ galerii, a dolne nagłówki, przyciski, FAQ i linki nie były
zachowane. 3.4.1 renderuje pełne źródło każdej oferty dokładnie raz i zachowuje
oryginalne sekcje, wiersze, kolumny, indeksy modułów oraz wszystkie dolne elementy.
Wycofany kontakt ze zdjęciem pokrętła pozostaje usuwany.

Nowy opis zastępuje tylko rozpoznany wstęp tekstowy na 39 ofertach, po sprawdzeniu
SHA źródła i wykonaniu callbacku. Flagi oraz totemy mają we wstępie zdjęcia;
całe ich wprowadzenie pozostaje, a szczegóły zamówienia pojawiają się pod nim jako
osobny blok bez powtarzania wstępu. Późniejsza ręczna edycja wstępu lub banera
powoduje zachowanie oryginalnego układu. Na sześciu nowych stronach treść
wtyczki nadal pojawia się raz, z ochroną późniejszych edycji.

Wyłączono wymuszone odstępy i rozmiary tekstów 3.4 w zachowanych sekcjach ofert.
W bazie nie są zmieniane ani treści, ani Rank Math. Do aktualizacji wystarczy
zastąpić wtyczkę, odświeżyć wygląd i wyczyścić także LiteSpeed Cache; nie trzeba
ponownie zapisywać SEO ani tworzyć sześciu stron.


## Kompaktowa oferta i mobilny nagłówek — 3.4.2

Pięć paneli ma na komputerze trzy kolumny w pierwszym rzędzie i dwa równe
bloki w drugim. Węższe ekrany przechodzą do dwóch kolumn, telefony do jednej.
Nagłówki paneli używają Manrope 800, 13 px na komputerze i 12 px na telefonie,
przy tekście list 15 px. Nazwy pozostają wielkimi literami, bez ukrywania treści.
Mniejsze odstępy i dwukolumnowy wstęp ograniczają pustą przestrzeń. W lokalnym
porównaniu Banerów wysokość siatki spadła z 776,75 do 413 px (o 46,8%).

Do 760 px zdjęcie jest tłem jednego nagłówka, zamiast osobnej wysokiej sekcji
pod tekstem. Ciemny gradient, krótsze odstępy, dopasowane długie nazwy i dwa
równe przyciski o wysokości co najmniej 46 px porządkują górę. Pominięto
mobilny podpis dekoracyjny zdjęcia. Dolny przycisk wyceny ma ciemny tekst
bez podkreślenia, także wewnątrz oryginalnego kontenera treści Divi.

Zmiana dotyczy wyłącznie CSS nowych paneli oraz nagłówka oferty. Renderery,
szablony, oryginalne galerie i dolne moduły z naprawy 3.4.1, pakiet tekstów,
metadane oraz pomiar Site Kit zachowują identyczne bajty. Do aktualizacji
wystarczy zastąpić wtyczkę i wyczyścić cache; nie trzeba ponownie zapisywać SEO
ani tworzyć sześciu stron. Fotografie produkcyjne nie były dostępne w testach.


## O nas i Kontakt — 3.5.0

Obie podstrony mają wspólną, kompaktową oprawę z jasnym nagłówkiem, ciemnym
tekstem i ciemniejszym pomarańczem na jasnym tle. Manrope porządkuje treści,
listy, FAQ i karty zespołu. Nagłówki treści mają do 30 px na komputerze i 23 px
na telefonie; dawny kontaktowy napis 9em jest ograniczony do tej samej skali.
Odstępy między sekcjami wynoszą 28 px na komputerze i 20 px na telefonie.
Szerokość jest ograniczona do 1280 px z płynnymi marginesami.

Nowy nagłówek zastępuje tylko dokładnie rozpoznany dawny baner O nas z pustą
połową oraz tekstowy wstęp kontaktu. Zrezygnowano z dodatkowych kart biur
wstawianych przez starszy szablon wtyczki: pozostają oryginalne karty zespołu,
z tymi samymi numerami i adresami. Układ dwóch kart przechodzi do jednej
kolumny przy 640 px. Mapa ma wysokość 320 px na większych ekranach i 240 px
na telefonie. Link do trasy i opinie pozostają dostępne.

Wszystkie oryginalne callbacki Divi wykonują się raz. Nowa warstwa dodaje
klasy do istniejących modułów, zachowując sekcje, wiersze i kolumny. Nie
wyciąga samych zdjęć ani kodu do nowej siatki. O nas zachowuje siedem zdjęć,
osiem FAQ, dwa niepuste bloki narracyjne oraz pozostałe teksty. Kontakt
zachowuje mapę i widget Trustindex; moduł z samym shortcode opinii nie jest
traktowany jako puste pole. Formularze i późniejsze dodatki pozostają.

Rozpoznanie wymaga dokładnych hashy oryginalnych modułów albo wariantów
z dołączonego pakietu SEO. Ręczne zmiany nie są usuwane. Znane końcowe H1
wezwania do kontaktu może być wyświetlone jako H2; jeżeli treść już zawiera
H1, nowy tytuł ma semantykę akapitu. Ukrywanie starego banera następuje
po wykonaniu callbacków, z zachowaniem numeracji stylów Divi.

CSS tego komponentu dotyczy tylko O nas i Kontaktu. Nie zmienia reguł
galerii ofert, formularzy zapisu SEO ani pomiaru Site Kit.


## Strona główna, katalogi i stopka — 3.5.0

Strona główna ma pięć slajdów: firmę YOONA, banery, litery 3D, oklejanie
i neony. Pierwszy zachowuje zdjęcie firmy. Każdy slajd prowadzi do właściwej
oferty, a wejście tekstu ma krótką animację. Dostępne są strzałki, wybór
slajdu i pauza; automatyczne przejście zatrzymuje się przy obsłudze klawiaturą,
najechaniu, ukrytej karcie i preferencji ograniczonego ruchu. Bez JavaScript
widoczny jest pierwszy slajd z działającymi linkami. Tylko pierwsza fotografia
otrzymuje priorytet pobierania; pozostałe są ładowane leniwie.

Osiem kart zdjęciowych i dalsze kategorie prowadzą łącznie do wszystkich
47 ofert bez powtórzeń. Kategorie otrzymują przypisane tematycznie ikony SVG.
W stopce Facebook, Instagram, TikTok i YouTube mają rozpoznawalne ikony
oraz czytelne nazwy, przy zachowanych adresach profili.

Pełna Oferta pokazuje od razu wszystkie 47 publicznych ofert, każdą jako
zdjęcie i tytuł. Nasze realizacje pokazują 24 opublikowane prace ze zdjęciami
na stronę i natywną paginację WordPressa. Zastępuje to limit 30 kart ze
starszego wydania. Na ekranach od 1400 px oba katalogi mają sześć kolumn;
poniżej przechodzą kolejno do czterech, trzech, dwóch i jednej. Pełna
szerokość ma płynne marginesy od 16 do 56 px. Tytuły nie są skracane,
a karty realizacji zawierają tylko zdjęcie i tytuł. Zapytanie nadal pomija
szkice, wpisy prywatne, chronione i pozbawione odpowiedniego zdjęcia.
Kolejność pozostaje od najnowszych z rozstrzygnięciem remisu przez ID.


## Pojedyncze realizacje i przeglądanie zdjęć — 3.5.0

Pojedyncza realizacja ma kompaktowy nagłówek, oryginalną treść oraz układ
dopasowany do liczby zdjęć. Jedna fotografia nie jest powtarzana jako drugi
baner; wielozdjęciowe galerie zachowują całość. Oryginalne sekcje Divi,
wiersze, kolumny, podpisy, formularze, FAQ i odnośniki wykonują się raz
i pozostają razem. Na telefonie układ przechodzi do jednej kolumny.
Nie zmienia to naprawionych galerii ani dolnych sekcji podstron ofertowych.

150 rozpoznanych realizacji ma indywidualny, oparty na źródle opis i linki
do pasujących ofert. Dalsza wskazówka planowania jest właściwa dla usługi
i odróżniona od faktów o wykonanej pracy. Informacja o obsłudze Olsztyna,
Ostródy oraz Warmii i Mazur nie oznacza przypisywania każdej pracy tym
lokalizacjom. Ręcznie zmienione albo nierozpoznane wpisy zachowują pełną
aktualną treść; nie otrzymują dopisanych faktów o realizacji.

Zdjęcia w obsługiwanych galeriach i realizacjach można otworzyć w nowej
przeglądarce zdjęć. Nie pokazuje nazw plików ani podpisów nad fotografią.
Pod zdjęciem, w jego szerokości, mieści dwa numery telefonów i przejście
do wyceny. Dostępne są klawiatura, zamknięcie Escape, przejścia między
zdjęciami i powrót fokusu do otwierającego elementu. Oryginalne podpisy
w treści strony pozostają. Istniejące linki zdjęć do innych podstron nie
są przejmowane jako galerie.


## Fotografie usług, SEO i zestaw wtyczek — 3.5.0

Sześć dodatkowych podstron CNC i markiz ma różne fotografie nagłówków
i kart: łącznie 12 odrębnych adresów istniejących zdjęć realizacji YOONA.
Wybór oraz pochodzenie opisuje [przegląd SEO i wtyczek](PRZEGLAD-SEO-I-WTYCZEK.md).
Zdjęcie realizacji markizy nie jest przedstawiane jako potwierdzenie
konkretnego modelu ani producenta, jeśli źródło tego nie podaje.

Lokalny audyt sprawdza kompletność i unikalność tytułów oraz opisów,
publiczne dane ofert i realizacji, linkowanie wewnętrzne i przypisania
zdjęć. To kontrola pakietu i kodu. Dostęp do żywej strony yoona.pl jest
w środowisku zablokowany, dlatego nie potwierdzono aktualnego indeksowania,
Core Web Vitals, wyników Search Console ani pozycji w Google.

Nie dodano nowych wtyczek. Rank Math Free i PRO pozostają wspólnie,
Site Kit jest istniejącym źródłem tagu Analytics, a pomiar kontaktów
zachowuje obsługę zgody. Przegląd wskazuje sprawdzenie, czy Imagify
i LiteSpeed nie optymalizują tych samych zdjęć równolegle; aktualizacja
nie zmienia samodzielnie ustawień tych narzędzi ani kont Google.


## Edycja Divi i aktualizacja — 3.5.0

Do samej zmiany wyglądu wystarczą trzy kroki:

1. Wgraj ZIP 3.5.0 w **Wtyczki → Dodaj nową → Wyślij wtyczkę na serwer**
   i zastąp obecną wersję YOONA.
2. W **Narzędzia → Yoona SEO** odśwież wygląd.
3. W **LiteSpeed Cache → Narzędzia → Opróżnianie** wybierz **Purge All**.

Nie trzeba ponownie zapisywać już wdrożonego SEO ani tworzyć ponownie sześciu
podstron. Sam ZIP nie publikuje zmian na serwerze — pojawią się po aktualizacji
WordPressa i wyczyszczeniu jego cache. Na produkcji nie wykonano zdalnej instalacji.

Aby zmieniać nowy układ w Divi, otwórz wybraną stronę lub wpis w zwykłej
edycji WordPressa, a następnie panel **YOONA — edycja układu w Divi**.
Przycisk **Edytuj układ w Divi** pokazuje źródło do przeglądu;
**Zapisz układ i edytuj w Divi** tworzy kopię i zapisuje moduły tej jednej
strony. Potem sekcje, wiersze, kolumny, teksty, zdjęcia, przyciski i slajdy
można edytować i kopiować narzędziami Divi. Nie ma automatycznej masowej
migracji. Aktualizacja wtyczki nie zastępuje zapisanych później zmian.

Lista realizacji jest dynamicznym modułem Kod z `[yoona_realizacje]`
i wymaga aktywnej wtyczki YOONA; konkretne zdjęcia i tytuły zmienia się
w odpowiednich wpisach. Wspólny
nagłówek i stopka nadal pochodzą z wtyczki. Konwersja ma własną kopię,
weryfikację źródła, blokadę równoległego zapisu oraz odzyskiwanie ręcznych
zmian przed przywracaniem. Szczegóły: [edycja i cofanie Divi](EDYCJA-DIVI.md).

Testy działają na prawdziwym WordPressie, Rank Math i bazie oraz jawnych
testowych callbackach Divi. Licencjonowany Divi i jego Visual Builder nie
były uruchomione. Fotografie produkcyjne zastępowano oznaczonymi lokalnymi
obrazami; testy potwierdzają układ i zachowanie, a nie dostarczenie zdjęć
ani formularzy na yoona.pl. Faktycznie wykonane przebiegi i ich zakres
podaje [dokument testów](WYNIKI-TESTOW.md).

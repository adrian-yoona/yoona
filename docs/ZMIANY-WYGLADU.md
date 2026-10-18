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

Wygląd jest domyślnie włączoną warstwą szablonów wtyczki. Nie zapisuje nowego układu do `post_content`, nie usuwa modułów Divi i nie zmienia adresów. Strona główna ma własną kompozycję, a standardowa treść podstron jest przetwarzana przez WordPress. Zmiany w dotychczasowym układzie Divi strony głównej będą widoczne po wyłączeniu nowego szablonu; nie edytują nowej kompozycji.

Panel **Narzędzia → Yoona SEO** pokazuje stan wyglądu, przełącznik powrotu i odświeżenie cache. Wyłączenie wyglądu lub dezaktywacja wtyczki przywraca szablony motywu. Wcześniej zapisane teksty i metadane Rank Math pozostają i mają osobne kopie w historii.

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

=== Yoona — wygląd i SEO ===
Contributors: yoona
Tags: seo, content, divi, design
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 3.4.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Nowy wygląd yoona.pl według wzoru właściciela oraz poprawki tekstów Divi i metadanych Rank Math.

== Description ==

Wersja 3.4.1 naprawia galerie i dół wszystkich ofert. Zamiast wyjmowania samych mediów zachowuje pełny oryginalny układ Divi, jego sekcje, wiersze, kolumny, przyciski, FAQ i pozostałe dolne elementy. Nowy opis zastępuje tylko dokładnie rozpoznany wstęp tekstowy; flagi i totemy zachowują wstępy ze zdjęciami. Ręcznie zmienione sekcje pozostają w całości. Naprawa nie przepisuje bazy, nie wymaga ponownego zapisu SEO ani ponownego dodawania stron. Po aktualizacji wyczyść również LiteSpeed Cache.

Wersja 3.4 porządkuje treści 41 ofert, rozbudowuje CNC oraz markizy SELT i dodaje sześć osobnych podstron po kliknięciu administratora. Materiały, zastosowania, proces, wycena i pytania mają krótkie, czytelne sekcje. Główna pokazuje wszystkie dostępne kategorie. Olsztyn i Ostróda mają równorzędne kontakty obok siebie także na telefonie.

150 rozpoznanych realizacji otrzymuje krótkie opisy oparte na potwierdzonych faktach, z zachowaniem zdjęć i galerii. Później zmienione realizacje zachowują oryginalny widok dzięki kontroli skrótu całej treści. Lista realizacji nadal pokazuje 30 zdjęć z tytułami na stronę.

Nowe opisy istniejących ofert są częścią odwracalnego wyglądu; nie nadpisują zapisanej treści Divi ani metadanych. Sześć nowych stron tworzy się osobnym przyciskiem Dodaj 6 podstron CNC i markiz, z natywnymi polami Rank Math. Istniejące adresy są pomijane. Cofanie przenosi do kosza wyłącznie utworzone, całkowicie niezmienione strony.

Skrypt pomiarowy korzysta z istniejącego Google tag w Site Kit po rozpoznanej zgodzie analitycznej CookieYes lub WP Consent API. Nie ładuje drugiego tagu, nie przekazuje danych formularzy i nie oznacza kliknięć jako leadów. Wariant GTM jest używany tylko przy braku gtag i wymaga istniejącej konfiguracji. Konta Google nie są modyfikowane.

Wersja 3 dodaje czerń i pomarańcz, duże nagłówki, zdjęciowe karty usług, proces, realizacje i kontakt. Nowa kompozycja strony głównej oraz wspólna oprawa podstron są domyślnie włączone po aktualizacji. Stan wyglądu, odświeżenie cache i powrót do motywu są dostępne w Narzędzia → Yoona SEO.

Warstwa wizualna używa szablonów wtyczki i nie zastępuje układu Divi w bazie. Stronę główną nowej kompozycji edytuje się w plikach szablonu; dotychczasowa treść Divi pozostaje dostępna po wyłączeniu wyglądu. Zdjęcia i odnośniki pochodzą z istniejącej witryny, a Archivo Black i Manrope są dostarczane lokalnie z licencjami OFL. Wygląd można wyłączyć bez cofania zapisanych metadanych.

Wersja 3.2 dodaje wspólny nagłówek 41 podstron ofertowych: duże zdjęcie, nazwę usługi, krótki opis i przejście do wyceny. Zastępuje rozpoznany stary baner tylko podczas wyświetlania; pozostałe moduły Divi są renderowane w dotychczasowej kolejności. Obsługuje nagłówki przed i po zapisie paczki SEO. Jeśli stary baner został zmieniony i nie odpowiada sprawdzonemu wzorcowi, zachowany jest poprzedni widok.

Wersja 3.3.1 usuwa z widoku powtarzany blok Masz pytanie? Kontakt ze zdjęciem pokrętła na wszystkich publicznych podstronach. Rozpoznaje sam komponent po zdjęciu i treści kontaktowej, także po edycji, pod nowym adresem i w bezpośrednim renderowaniu Divi. Nie wymaga zgodności całej sekcji z eksportem. Pełna Oferta pokazuje zdjęciowe karty wszystkich dostępnych usług w stylu strony głównej. Nasze realizacje pokazują po 30 prac ze zdjęciem i tytułem, w trzech kolumnach na komputerze, z kolejnymi stronami. Oba widoki zajmują dostępną szerokość ekranu z równymi marginesami. Wpisy bez zdjęcia nie trafiają do siatki realizacji.

Pakiet opracowano na podstawie eksportu właściciela yoona.pl z 15 października 2026 r. Obejmuje metadane 200 elementów oraz 896 zmian tekstów w 56 stronach i wpisach.

Tytuły i opisy są zapisywane w natywnych polach Rank Math. Zmiany pozostają w Rank Math również po wyłączeniu Yoona SEO. Poprawki tekstów obejmują wnętrza istniejących modułów Divi et_pb_text i odpowiedzi et_pb_toggle; shortcode, atrybuty, galerie, obrazy, przyciski oraz formularze są zachowane.

Wygląd działa osobno od pakietu tekstów. Aktywacja i wybór paczki nie zapisują metadanych ani zmian post_content. Administrator sprawdza podgląd i wybiera strony. Przycisk wdrożenia wykonuje zapis partiami po 10 elementów i tworzy osobną kopię każdej partii. Historia pokazuje wszystkie partie. Hash całej treści i modułów chroni nowsze edycje; cofanie sprawdza konflikty.

Wtyczka nie zmienia adresów, robots, canonical, danych produktów ani mediów. Nie wysyła treści do zewnętrznych usług i nie wymaga klucza API. Dostępne jest również pobranie publicznych treści i wybranych ustawień do kolejnego audytu. Eksportu nie publikuj.

Audyt oparto na eksporcie. Nie potwierdza pozycji w Google, indeksacji, szybkości ani wyglądu licencjonowanego motywu Divi na produkcji. Nie gwarantuje rezultatów SEO.

== Installation ==

1. Wykonaj pełną kopię witryny w hostingu.
2. Wgraj yoona-seo-v3.4.1.zip w Wtyczki → Dodaj nową → Wyślij wtyczkę na serwer.
3. Zastąp poprzednią wersję Yoona SEO, jeśli jest zainstalowana, i pozostaw Rank Math aktywny.
4. Odśwież Narzędzia → Yoona SEO i sprawdź wersję 3.4.1. Użyj Odśwież wygląd i wyczyść cache, a potem otwórz Pełną Ofertę i Nasze realizacje. Nowy wygląd jest domyślnie włączony, o ile wcześniej nie został wyłączony. Do tych zmian wyglądu nie trzeba ponownie zapisywać SEO.
5. Aby opublikować sześć nowych usług, kliknij Dodaj 6 podstron CNC i markiz. To osobny zapis stron; powtórzenie nie tworzy duplikatów.
6. Jeżeli poprawki tekstów/SEO nie są zapisane, wybierz Użyj pakietu dołączonego do wtyczki, sprawdź podgląd, wybierz Zaznacz wszystkie gotowe i Wgraj zmiany na zaznaczone strony. Pozostaw kartę otwartą do zakończenia.
7. Sprawdź wygląd, galerie i formularze na komputerze oraz telefonie. Cache hostingu lub CDN może wymagać czyszczenia we własnym panelu.

== Frequently Asked Questions ==

= Czy sama instalacja poprawia stronę? =
Wersja 3 domyślnie włącza nowy wygląd. Zapis tekstów i metadanych nadal uruchamia się osobno przyciskiem wgrania zmian. Wersje 1–2 nie zawierały przebudowy wizualnej.

= Jak wrócić do poprzedniego wyglądu? =
Wyłącz nowy wygląd w Narzędzia → Yoona SEO. Dezaktywacja wtyczki również przywraca szablony motywu. Zapisane teksty i Rank Math mają osobną historię cofania.

= Czy mogę cofnąć zmiany? =
Tak, w historii partii. Późniejsze zmiany objętych pól mogą spowodować konflikt, który trzeba sprawdzić. Kopie wtyczki nie zastępują pełnego backupu hostingu.

= Dlaczego strona jest zablokowana do aktualizacji? =
Jeśli treść zmieniła się po eksporcie, kontrola hashy chroni nowszą pracę. Potrzebny jest nowy eksport i zaktualizowany pakiet. Wdrożenie wymaga aktywnego Rank Math i uprawnień administratora do edycji strony.

= Czy po wyłączeniu wtyczki poprawki znikną? =
Warstwa wizualna zniknie. Zapisane zmiany treści i natywnych pól Rank Math pozostają. Aby skorzystać z historii cofania, zachowaj Yoona SEO. Starsze paczki wersji 1 mogły używać filtrów własnych metadanych i wymagać aktywnej wtyczki do ich wyświetlania.

== Changelog ==

= 3.4.1 =
* Naprawione galerie i dolne sekcje ofert przez zachowanie całego oryginalnego drzewa Divi.
* Zachowane przyciski, nagłówki galerii, FAQ, linki social oraz numeracja wszystkich renderowanych modułów.
* Nowe opisy tylko w rozpoznanych wstępach; zachowane zdjęcia flag i totemów oraz późniejsze ręczne edycje.
* Usunięte wymuszanie odstępów 3.4 na oryginalnych sekcjach ofertowych.

= 3.4.0 =
* 41 uporządkowanych ofert, rozbudowane CNC i markizy SELT oraz sześć podstron dodawanych osobnym przyciskiem.
* 150 faktograficznych opisów realizacji z kontrolą źródła i zachowaniem fotografii oraz galerii.
* Wszystkie kategorie na głównej, menu Pełna oferta i równorzędny kontakt Olsztyn/Ostróda także na telefonie.
* Mniejsze odstępy i nagłówki, pełna szerokość galerii oraz zachowanie oryginalnych formularzy.
* Pomiar kliknięć przez istniejący Site Kit z rozpoznaną zgodą CookieYes/WP Consent API, bez drugiego tagu i danych formularzy; wariant GTM bez podwójnej wysyłki.

= 3.3.1 =
* Naprawione usuwanie starego bloku kontaktowego po zmianach tekstów, atrybutów Divi lub adresu strony.
* Obsługa wszystkich publicznych podstron, kopii sekcji, bezpośredniego renderera Divi i gotowego HTML.
* Rozpoznanie zdjęcia i treści komponentu zamiast dokładnego hasha; zachowana treść poza sekcją, liczniki modułów oraz ochrona edytora.

= 3.3.0 =
* Pominięcie rozpoznanego starego bloku Masz pytanie? Kontakt na 44 podstronach, bez kasowania treści w bazie.
* Pełna Oferta jako zdjęciowe karty usług w stylu strony głównej.
* Nasze realizacje: 30 zdjęć z tytułami na stronę, trzy kolumny na komputerze i natywna nawigacja kolejnych stron.
* Szeroki układ z równymi marginesami, dopasowane zdjęcia i responsywne siatki.

= 3.2.0 =
* Jeden spójny nagłówek zamiast osobnego tytułu i starego banera na 41 podstronach usługowych.
* Duże fotografie z istniejącej witryny, opisy usług, nawigacja i przyciski wyceny; układ dostosowany do telefonu.
* Kontrola hashy starych banerów, zachowanie liczników modułów i stylów Divi, brak zmian w bazie treści i SEO.
* Zachowana naprawa zapisu SEO z wersji 3.1.1.

= 3.1.1 =
* Naprawiony adres wysyłania zmian SEO: pole formularza action zasłaniało właściwość action formularza i powodowało błąd 404 przed rozpoczęciem zapisu.
* Czytelne komunikaty z kodem HTTP przy nieprawidłowej odpowiedzi i przekierowaniu do logowania. Zapis zatrzymuje się bez automatycznych powtórzeń.
* Zachowane partie po 10 elementów, kopie, cofanie i kontrola nowszych edycji.

= 3.1.0 =
* Poprawione warstwy przyciemnienia zdjęcia, kadr i czytelność nagłówka.
* Lokalne fonty Manrope i Archivo Black, większe opisy i mniej ściśnięte litery.
* Spójne wielkie litery nazw usług, paska procesu i nagłówków pełnej oferty.

= 3.0.0 =
* Nowy wygląd według wzoru: strona główna, nawigacja, podstrony i stopka; responsywne menu.
* Włączenie po aktualizacji, powrót do motywu i odświeżenie cache w panelu.
* Osobne podsumowanie gotowych, zapisanych i zablokowanych poprawek SEO.
* Lokalne fonty Archivo Black, istniejące zdjęcia i zachowanie treści Divi w bazie.

= 2.0.0 =
* Gotowy pakiet 200 metadanych i 896 zmian tekstów z eksportu właściciela.
* Bezpośredni zapis Rank Math, kontrolowane poprawki wnętrz modułów Divi i FAQ.
* Wdrożenie partiami, podgląd, kopie każdej partii, cofanie i kontrola konfliktów.

= 1.0.0 =
* Eksport diagnostyczny i podgląd pakietu.

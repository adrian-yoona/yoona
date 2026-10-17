# Pomiar kontaktów i projekt zmian Google Ads

To specyfikacja pomiaru z istniejącym Google Site Kit oraz szkic reklam do oceny.
Użytkownik potwierdził Site Kit połączony z Analytics, CookieYes oraz aktywne
LiteSpeed Cache i Imagify. Nie zakładamy obecności Google Tag Managera. Wtyczka
nie loguje się do Google, nie publikuje tagów, kampanii ani zmian budżetu.
Analiza prywatnych eksportów odbyła się oddzielnie.

## Jakie zdarzenia udostępnia wtyczka

`assets/measurement.js` reaguje na kliknięcia linków telefonu, e-maila i przycisków
wyceny. Preferowana droga to istniejące `gtag('event', nazwa, parametry)`
udostępniane przez Site Kit. Wtyczka nie pobiera drugiego Google Tag, nie tworzy
nowej usługi i nie zapisuje własnych cookies. Istniejący tag zachowuje swoje
ustawienia miejsca docelowego i Consent Mode.

Zdarzenie jest przekazywane dopiero przy wykrytej pozytywnej zgodzie analitycznej
z CookieYes lub publicznego WordPress Consent API, bez rozpoznanej odmowy.
Nieznany stan i odmowa pomijają kliknięcie; nie ma kolejki do późniejszego
odtwarzania. Kod nie ustawia zgody `granted`. Nie korzysta z prywatnego,
potencjalnie nieaktualnego obiektu zgód Site Kit.

Jeśli zamiast dostępnego `gtag` działa wyłącznie istniejąca warstwa `dataLayer`,
alternatywą jest obiekt zdarzenia i konfiguracja GTM opisana niżej. Dla jednego
kliknięcia wybierana jest tylko jedna droga. Połączenie Analytics w Site Kit
nie dowodzi jeszcze, że kod pomiaru działa na froncie dla gościa.

| Nazwa | Znaczenie | Czy jest leadem? |
|---|---|---|
| `yoona_contact_click` | Kliknięcie `tel:` lub `mailto:` | Nie — zamiar kontaktu |
| `yoona_quote_click` | Kliknięcie linku z etykietą wyceny lub przesłania projektu | Nie — przejście do kolejnego kroku |

| Parametr / klucz dataLayer | Dopuszczalne wartości / zakres | Parametr GA4 |
|---|---|---|
| `contact_method` | `phone`, `email`, `quote_page` | `contact_method` |
| `contact_location` | `Olsztyn`, `Ostróda`, `not_specified` | `contact_location` |
| `contact_placement` | `header`, `footer`, `content` | `contact_placement` |
| `page_path` | Ścieżka aktualnego URL, bez query i fragmentu | `page_path` |

Brak nazwy klienta, numeru telefonu klienta, jego adresu e-mail, treści wiadomości
oraz wartości pól formularza. Jeżeli miejsce kliknięcia nie określa oddziału,
pozostaje `not_specified`; nie przypisujemy go na siłę do Olsztyna.

## Ścieżka podstawowa: istniejący Site Kit i GA4

1. W WordPressie sprawdzić ustawienia Site Kit → połączone usługi → Analytics:
   właściwą usługę i strumień oraz to, czy Site Kit wstawia kod Analytics, czy
   korzysta z kodu umieszczonego przez inną integrację. Nie zmieniać identyfikatora
   usługi ani nie dodawać GTM tylko po to, aby obsłużyć te zdarzenia.
2. Sprawdzić stronę jako wylogowany gość i w Tag Assistant. Site Kit może
   wykluczać zalogowanych administratorów z pomiaru. Zweryfikować jeden kod
   Analytics i brak drugiego `page_view` z motywu, innej wtyczki lub GTM.
3. W CookieYes sprawdzić kategorię analityczną i współpracę z Consent Mode lub
   WordPress Consent API. Kliknięcia Yoona nie mogą nadpisywać odmowy zgody.
   Nie włączać własnego domyślnego `granted`. Przetestować zgodę, odmowę i jej
   zmianę w nowej sesji; brak wykrywalnej zgody nie jest zgodą.
4. Po wyrażeniu zgody kliknąć jeden telefon, e-mail i przycisk wyceny. W Tag
   Assistant i GA4 DebugView sprawdzić nazwy oraz parametry z tabel. DebugView
   wymaga aktywnego trybu debugowania — samo otwarcie tego raportu go nie włącza.
   Następnie potwierdzić zdarzenia w odpowiedniej usłudze GA4. Raporty standardowe
   mogą pojawić się z opóźnieniem.
5. W GA4 dodać wymiary niestandardowe o zakresie zdarzenia dla
   `contact_method`, `contact_location`, `contact_placement`. Ocenić, czy osobny
   wymiar dla `page_path` jest potrzebny; nie tworzyć zbędnych pól.
6. W GA4 przejrzeć zdarzenia i ich oznaczenia jako kluczowe. Na tym etapie
   `yoona_contact_click` oraz `yoona_quote_click` są zdarzeniami pomocniczymi,
   bez automatycznego przypisania wartości lub oznaczenia jako pozyskany lead.
   Site Kit może pokazywać dane Analytics, ale zrzut połączenia nie potwierdza
   właściwych definicji zdarzeń kluczowych.

Bezpośrednia droga Site Kit nie wymaga tworzenia tagu GA4 w GTM. Jeśli Site Kit
nie wstawia kodu, najpierw ustalić istniejącego właściciela tagu i usunąć przyczynę
braku pomiaru; instalacja kolejnej wtyczki lub kontenera może zdublować dane.

## Ścieżka alternatywna: tylko potwierdzony istniejący GTM

Stosować tylko wtedy, gdy pomiar ma obsługiwać istniejący kontener GTM,
a bezpośrednia droga `gtag` nie wysyła tych samych zdarzeń. GTM nie został
potwierdzony samym zrzutem Site Kit.

1. W istniejącym kontenerze utworzyć cztery zmienne „Zmienna warstwy danych”,
   wersja 2, o nazwach kluczy z tabeli. Włączyć wbudowaną zmienną `Event`.
2. Utworzyć wyzwalacz „Zdarzenie niestandardowe”, wyrażenie regularne
   `^yoona_(contact|quote)_click$` z zaznaczonym dopasowaniem regex.
3. Utworzyć jeden tag „Google Analytics: zdarzenie GA4” korzystający z istniejącej
   konfiguracji usługi. Nazwa zdarzenia: `{{Event}}`. Dodać cztery parametry
   z tabeli i odpowiadające im zmienne warstwy danych.
4. Powiązać tag z wyzwalaczem. Dostosować uruchomienie do zgód obsługiwanych przez
   CookieYes i konfigurację Consent Mode. Ten obiekt nie jest zgodą na
   analitykę. W trybie wymagającym uprzedniej zgody tag powinien oczekiwać na
   `analytics_storage=granted`. Nie dodawać własnego domyślnego `granted`.
5. Przed publikacją sprawdzić Preview GTM i DebugView. Jeden klik powinien dać
   jeden obiekt i jedno zdarzenie, po uwzględnieniu warunków zgody. Obejrzeć
   rzeczywisty payload i potwierdzić brak wartości formularza i query string.

Wtyczka sama nie konfiguruje Google Tag Managera ani nie oznacza zdarzeń jako
kluczowe. Kliknięcie linku wyceny nie potwierdza otwarcia klienta poczty, rozmowy
telefonicznej ani wysłania wiadomości.

## Potwierdzony formularz i import konwersji

Do głównej konwersji potrzebny jest potwierdzony sukces formularza po stronie
serwera, nie kliknięcie przycisku „Wyślij” i nie samo wejście na kontakt.
Najpierw sprawdzić integracje formularzy, które już obsługuje Site Kit. Oficjalny
kod Site Kit zawiera provider Contact Form 7 i nazwę zdarzenia `contact`;
obecność tego mechanizmu w projekcie nie potwierdza jego aktywności w konkretnej
wersji i konfiguracji witryny. Nie dodawać drugiego słuchacza ani `generate_lead`,
zanim nie sprawdzono zdarzeń już wysyłanych przy udanym formularzu.

Jeżeli potrzebna jest własna integracja Contact Form 7, zdarzenie `wpcf7mailsent`
jest punktem integracji po odpowiedzi sukcesu. `wpcf7submit` występuje również
przy błędach i nie powinno samodzielnie oznaczać pozyskania leada.

Po sprawdzeniu istniejących integracji można zastosować schemat:

```text
Potwierdzony sukces serwera
→ jedno wybrane zdarzenie: istniejący contact z Site Kit LUB własny generate_lead
→ potwierdzenie jego definicji, pojedynczego wysłania oraz zgód
→ oznaczenie tego jednego zdarzenia jako kluczowego w GA4
→ jeden wybrany import konwersji do Google Ads
```

Nie wdrażać kolejnego `generate_lead` lub `contact`, jeżeli już istnieje.
Sprawdzić zachowanie przy błędzie walidacji, błędzie wysyłki, ponownym przesłaniu
i odświeżeniu strony.
Nie przesyłać treści formularza ani danych kontaktowych do GA4. Google Ads może
otrzymywać ten sam sukces zarówno z własnego tagu, jak i z importu GA4 — wybrać
jedno źródło główne, aby nie sumować tego samego leada dwukrotnie.

Kliknięcia kontaktu pozostawić do analizy pomocniczej. Za główne cele optymalizacji
uznawać dopiero uzgodnione, poprawnie mierzone kontakty. Rozmowę można mierzyć
odrębną konfiguracją połączeń; sam klik `tel:` nie dowodzi, że klient zadzwonił.
Jakość i wynik zlecenia oceniać w CRM lub innym rejestrze, nie na podstawie samego
zdarzenia w przeglądarce.

## Kontrola jakości GA4

W eksporcie występują podejrzany tytuł i referral `trafficheap.cc`. Sprawdzić
w eksploracji odpowiednie źródło/medium, hostname, page_location, identyfikator
strumienia, zdarzenia oraz rozkład dat. Nie traktować tego jako dowodu włamania
na stronę. Wykluczenie z widoku analizy przez porównanie/eksplorację pozwala
oddzielnie ocenić wpływ; nie usuwać danych historycznych.

Lista niechcianych witryn odsyłających służy regułom atrybucji i sama nie
zatrzymuje sztucznego ruchu ani nie oczyszcza historii. Filtry ruchu wewnętrznego
i deweloperskiego najpierw testować. Nie odrzucać całych państw lub miast na
podstawie pojedynczego wiersza raportu. Zbadać też, które zdarzenia oznaczono
jako kluczowe: zbiorczy licznik bez nazw nie jest liczbą leadów.

Podobnie wysoki odsetek zdarzeń kluczowych widoczny w kanale Site Kit wymaga
sprawdzenia definicji zdarzeń i wielkości próby. Nie oznacza automatycznie takiego
odsetka klientów ani sprzedaży. AdSense nie jest potrzebny do mierzenia zapytań
ofertowych YOONA; jego niepołączenie nie jest problemem tego wdrożenia.

## Projekt kampanii Ads do edycji

CSV-y są nowym szkicem, nie eksportem bieżącego konta i nie zmianą jego kampanii.
Nie zawierają budżetów, stawek, strategii automatycznego określania stawek ani
obietnicy wyniku. Nazwy kampanii robocze można dopasować do istniejącej struktury.

| Kampania robocza | Grupy | Zasięg do ustalenia w koncie |
|---|---|---|
| YOONA — Marka | Nazwa firmy i warianty z miastami | Faktyczny obszar obsługi; ocena niejednoznaczności nazwy |
| YOONA — Lokalne usługi | Agencja, reklama świetlna, kasetony, litery 3D, banery, oklejanie aut, oklejanie szyb | Olsztyn, Ostróda i potwierdzony obszar dojazdu |
| YOONA — CNC | Frezowanie elementów reklamowych | Potwierdzony zakres obsługi i transportu |
| YOONA — Markizy | Markizy z logo, markizy DUO | Polska, zgodnie ze wskazanym przez właściciela zakresem |

Na starcie rozważyć dopasowania ścisłe i do wyrażenia; nadal wymagają przeglądu
rzeczywistych wyszukiwanych haseł, ponieważ nie są dosłownym dopasowaniem tekstu.
Intencja frezowania elementów różni się od zakupu frezarki, a reklama na aucie
od zmiany koloru lakieru folią. Dodatkowe grupy tworzyć po ocenie danych i oferty.

Dla kampanii lokalnych sprawdzić ustawienia lokalizacji i faktyczny pobyt klienta
w obszarze; same zainteresowania miejscem mogą poszerzać zasięg. Dla markiz
zasięg krajowy ustawić oddzielnie. Nie włączać automatycznie innych sieci
i rozszerzeń zasięgu bez oceny jakości oraz celu kampanii. Budżet i stawki ustalać
po poznaniu kosztu kwalifikowanego leada, a nie zasięgu w przesłanym raporcie.

Gotowe materiały do przejrzenia i mapowania kolumn w Google Ads Editor:

- [Słowa i grupy](google-ads/keywords.csv) — warianty ścisłe i do wyrażenia,
  status `Paused`, istniejące strony docelowe.
- [Teksty RSA](google-ads/rsa.csv) — po pięć nagłówków i dwa opisy, status
  `Paused`; limity 30/90 znaków sprawdzone. Bez przypinania pozycji nagłówków.
- [Kandydaci do wykluczeń](google-ads/negative-candidates.csv) — lista do oceny,
  nie plik automatycznego importu. Każdy kandydat wymaga sprawdzenia wyszukiwanych
  haseł i zakresu kampanii.

Przed importem zaznaczyć właściwe konto, sprawdzić mapowanie angielskich kolumn
i podgląd zmian. Szkic nie tworzy pełnej konfiguracji kampanii: lokalizacje,
języki, harmonogram, cele, zasoby, zgody, budżety i strategię trzeba ustalić
w koncie. Zachować wstrzymanie podczas oceny. Nie publikować bez sprawdzenia
aktywnych duplikatów i poprawności stron docelowych. Samo zaimportowanie
wstrzymanego szkicu nie poprawia wyników.

Nie wykluczać automatycznie słów „cena”, „koszt”, „projekt”, „Ostróda”, „Olsztyn”
czy nazw obsługiwanych materiałów — mogą oznaczać wartościowe zapytanie.
Kandydaci z listy nie pochodzą z nieprzesłanego raportu haseł Google Ads; są
hipotezami branżowymi do weryfikacji.

## Co ocenić po wdrożeniu

Testowo wykonać kontakt dla obu miast na desktopie i telefonie, ze zgodą
i bez niej. Sprawdzić jedno naliczenie, sposób raportowania miejsca oraz
niezależność od danych formularza. Zweryfikować osobno potwierdzony sukces
formularza i jego błędy.

W kampaniach mierzyć koszty, kwalifikowane leady i zlecenia według usługi oraz
lokalizacji. Dopiero wtedy porównywać CPL, jakość zapytań i wartość sprzedaży.
Dostarczony ZIP zasięgu nie zawiera danych potrzebnych do takiego porównania.

Przy testach użyć istniejących LiteSpeed Cache i Imagify. Po aktualizacji
wyczyścić cache strony oraz przetestować kolejność CookieYes, Site Kit i skryptu
Yoona przy aktywnym opóźnianiu JavaScript. Nie dokładać drugiej wtyczki cache
lub drugiej instalacji Analytics w celu „naprawienia” brakującego zdarzenia.

## Oficjalne źródła i ograniczenia weryfikacji

17.10.2026 sprawdzono oficjalny kod projektu Site Kit, rewizja
`f7dceaec2be0b9b9395cc99cb7cac9b53903d07c`:

- [Google Site Kit — GTag](https://github.com/google/site-kit-wp/blob/f7dceaec2be0b9b9395cc99cb7cac9b53903d07c/includes/Core/Tags/GTag.php#L178)
  definiuje `gtag()` przez istniejące `dataLayer` i umieszcza konfigurację tagu.
- [Google Site Kit — Analytics 4](https://github.com/google/site-kit-wp/blob/f7dceaec2be0b9b9395cc99cb7cac9b53903d07c/includes/Modules/Analytics_4.php#L1233)
  zawiera ustawienia wstawiania kodu i wykluczania niektórych zalogowanych
  użytkowników z pomiaru.
- [Google Site Kit — Contact Form 7](https://github.com/google/site-kit-wp/blob/f7dceaec2be0b9b9395cc99cb7cac9b53903d07c/includes/Core/Conversion_Tracking/Conversion_Event_Providers/Contact_Form_7.php#L57)
  deklaruje zdarzenie `contact` w obsługiwanej integracji.

To odczyt źródła projektu, nie dowód aktualnej wersji na yoona.pl. Bezpośredni
odczyt [dokumentacji Site Kit](https://sitekit.withgoogle.com/documentation/)
i [dokumentacji zdarzeń GA4](https://developers.google.com/analytics/devguides/collection/ga4/events)
został odrzucony przez proxy `403 Forbidden`; nie opisujemy ich bieżącego
interfejsu jako sprawdzonego na żywo. Ustawienia usługi, zgody i pojedyncze
wysyłanie trzeba potwierdzić na produkcji jako gość po wdrożeniu.

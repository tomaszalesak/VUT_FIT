# Klient pro OpenWeatherMap API

Autor: Tomáš Zálešák

xzales13@stud.fit.vutbr.cz

# Zadání

Úkolem je vytvořit program - klienta pro API rozhraní OpenWeatherMap, který bude schopen prostřednictvím HTTP dotazů získávat vybrané informace z datového zdroje.

# Návrh řešení
Zvolil jsem implementaci v Pythonu. Potřeboval jsem knihovny:
- socket - komunikace pomocí soketů
- json - zpracování dat z odpovědi API na slovník
- sys - ukončení běhu programu a přístup k argumentům příkazové řádky
- re - regulární výraz pro oddělení JSON dat od zbytku HTTP odpovědi

Využívám TCP sokety. TCP sokety jsou
- spolehlivé - ztracení paketů v síti je detekováno a pakety jsou poslány znovu
- in-order data delivery - data jsou přečtena v pořadí, v jakém byla zapsána odesilatelem 

Postup při implementaci
- zpracování argumentů příkazového řádku
  - argument jména města
  - argument API klíče
- vytvoření HTTP požadavku, který obsahuje:
  - jméno serveru
  - cestu k našemu API
  - argumenty: město a API klíč, volba metrických jednotek
- zjištění informací o adrese serveru
  - volba IPv4/IPv6
- vytvoření soketu
- připojení na server
- poslání HTTP požadavku
- přijetí dat
- zavření soketu
- získání dat z odpovědi API ve formátu JSON
- výpis užitečných dat na standardní výstup

# Spuštění skriptu

## Požadavky
- python3 (testováno v Pythonu 3.6 a 3.7 na CentOS, na Windows můžou nastat problémy se sokety)
- Make (testováno na CentOS)

## Spuštění

Ve složce se skriptem zadejte do terminálu:

```
make run api_key=<API klíč> city=<Město>
```

Příklad

```
make run api_key=b498767252de12f92574d288a9c4fdc2 city=Prague
```

Ukázka výstupu
```
$ make run api_key=b498767252de12f92574d288a9c4fdc2 city=Prague

Prague
light rain
temperature: 8.55°C
humidity: 65%
pressure: 1006hPa
wind speed: 7.2m/s
wind direction: 250°
```

### Argumenty

Víceslovné argumenty je potřeba zadávat v uvozovkách.

# Rozšíření

## Podpora IPv4 i IPv6

Klient si zvolí správné připojení.

# Reference
- [https://tools.ietf.org/html/rfc7231](https://tools.ietf.org/html/rfc7231)
- [https://openweathermap.org/current](https://openweathermap.org/current)
- [https://realpython.com/python-sockets/](https://realpython.com/python-sockets/)
- [https://docs.python.org/3/library/socket.html](https://docs.python.org/3/library/socket.html)
- [https://docs.python.org/3/library/socket.html](https://docs.python.org/3/library/socket.html)
- 
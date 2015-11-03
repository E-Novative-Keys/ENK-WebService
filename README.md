ENK-WebService
=============

**ENK-WebService** est un WebService développé dans le cadre de la solution **E-Novative Keys**, en tant que partie de la *Mission 1* du projet annuel de 2ème année (2i) 2014-2015 à l'*École Supérieure de Génie Informatique* (ESGI) : **Webex**.

Basé sur le framework ENK-MVC, il permet l'interaction de la base de données et des fichiers de stockage INI avec l'application Java ENK-Projects et le site Web ENK-Cloud. Le WebService recevra toutes ses données en POST formattées en JSON de la façon suivante : 

```json
{
	"data": {
		"Token": {
			"link": "YOUR_TOKEN_ID",
			"fields": "YOUR_UNIQUE_TOKEN_VALUE"
		},
		"SOME_DATA_TYPE": {
			"SOME_KEY": "SOME_VALUE",
			...
		}
	}
}
```

Si toutes les données requises sont présentes et valides, le WebService effectuera alors son traitement et renverra des données en JSON.

Équipe
------------
* [Mathieu Boisnard](https://github.com/mboisnard)
* [Valentin Fries](https://github.com/MrKloan)
* Vincent Milano
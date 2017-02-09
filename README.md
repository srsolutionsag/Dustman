Dustman
========
Dustman is a simple Cron plugin which moves old ILIAS courses and groups into the trash. You can decide for the max. age
of a course/group after this time it gets moved automatically to the trash.

### Important Notice
**Use of this software happens solely on your own risk!!**

Please test the software extensively on your test environment.

If you have questions, studer + raimann ag can offer paid support services: info@studer-raimann.ch

### Features
* Excluding Methods:
  * Specify some categories who should be excluded.
  * Specify some keyword-tags which should be excluded.
* Get an automated e-mail x days before the object gets moved to the trash.

### Installation
You need ILIAS >= 4.4 to run this plugin.

In order to install the Dustman plugin go into ILIAS root folder and use:

```bash
mkdir -p Customizing/global/plugins/Services/Cron/CronHook
cd Customizing/global/plugins/Services/Cron/CronHook
git clone https://github.com/studer-raimann/Dustman.git
```

### Hinweis Plugin-Patenschaft
Grundsätzlich veröffentlichen wir unsere Plugins (Extensions, Add-Ons), weil wir sie für alle Community-Mitglieder zugänglich machen möchten. Auch diese Extension wird der ILIAS Community durch die studer + raimann ag als open source zur Verfügung gestellt. Diese Plugin hat noch keinen Plugin-Paten. Das bedeutet, dass die studer + raimann ag etwaige Fehlerbehebungen, Supportanfragen oder die Release-Pflege lediglich für Kunden mit entsprechendem Hosting-/Wartungsvertrag leistet. Falls Sie nicht zu unseren Hosting-Kunden gehören, bitten wir Sie um Verständnis, dass wir leider weder kostenlosen Support noch Release-Pflege für Sie garantieren können.

Sind Sie interessiert an einer Plugin-Patenschaft (https://studer-raimann.ch/produkte/ilias-plugins/plugin-patenschaften/ ) Rufen Sie uns an oder senden Sie uns eine E-Mail.

### Contact
studer + raimann ag  
Waldeggstrasse 72  
3097 Liebefeld  
Switzerland 

info@studer-raimann.ch  
www.studer-raimann.ch  

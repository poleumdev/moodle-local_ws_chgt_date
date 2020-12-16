# Outil de modification des dates d'un cours #

Web service qui permet de modifier la date de début d'un cours, toutes les autres dates des activités de ce cours sont alors modifiées relativement au nouveau début du cours.
Le webservice reçoit l'identifiant du cours et la nouvelle date de début du cours.

## Validation des paramètres : ##

* L'identifiant du cours doit appartenir à la colonne id de la table {course}.
* La date de début du cours cible (startdate) doit être renseignée.
* Le format de la nouvelle date de début doit se conformer aux formats supportés par la méthode PHP DateTime
* La nouvelle date de début doit être différente de l'actuelle date de début de cours, si elles sont identiques aucun traitement ne sera réalisé.

## Calcul du déplacement relatif ##

Le déplacement relatif à appliquer aux dates du cours est alors calculé de la façon suivante :

	timeshift = nouvelle_date_debut – ancienne_date_debut

## Liste des dates modifiées si elles étaient renseignées ##
`$course->enddate`	//date de fin du cours  
Toutes les dates relatives aux restrictions ('availability_date\condition')  
Toutes les dates d'achèvement attendu (completionexpected)  

### Date selon les activités (modules) ###


|  Modules  | Nom des champs date  | Report sur le calendrier étudiant              |
|-----------|----------|------------------|
| Assign (Devoir)| `duedate`, `allowsubmissionsfromdate`    | mod/assign/lib.php  |
|                | `gradingduedate`, `cutoffdate`           | assign_refresh_events($course->id); |
|   |  | |
| Assignment (Devoir)  | `timedue`, `timeavailable` | SANS  |
|   |  `timemodified`  | |
|   |  | |
| Choice (Sondage) | `timeopen`, `timeclose` | mod/choice/lib.php |
|  | | choice_refresh_events($course->id); |
|   |  | |
| _Forum_ (Forum) | `assesstimestart`, `assesstimefinish` | SANS |
|  | |  |
| Glossary (Glossaire) | `assesstimestart`, `assesstimefinish` | SANS |
|  | |  |
| Lesson (Leçon) | `available`, `deadline` | mod/lesson/lib.php |
|  | | lesson_refresh_events($course->id); |
|  | |  |
| Quiz (Test) | `timeopen`, `timeclose` | mod/quiz/lib.php |
|  | | quiz_refresh_events($course->id); |
|  | |  |
| Scorm (Scorm) | `timeopen`, `timeclose` | mod/scorm/lib.php |
|  | | scorm_refresh_events($course->id); |
|  | |  |
| Workshop (Atelier) | `submissionstart`, `submissionend` | mod/workshop/lib.php |
|  | `assessmentstart`, `assessmentend` | workshop_refresh_events($courseid); |
|  | |  |
| Questionnaire (questionnaire) | `opendate`, `closedate` | SANS |
|  | |  |
| Data (Base de données) | `timeavailablefrom`, `timeavailableto`, `timeviewfrom` | mod/data/lib.php |
|  | `timeviewto`, `assesstimestart`, `assesstimefinish` | data_refresh_events($course->id); |
|  | |  |


Note : les forums sont amenés à évoluer, à partir de Moodle 3.8 ils sont notés !

## [Informations pour développeurs](developp.md)





# Outil de modification des dates d'un cours #

Web service qui permet de modifier la date de début d'un cours, toutes les autres dates des activités de ce cours sont alors modifiées relativement au nouveau début du cours.
Le webservice reçoit l'identifiant du cours et la nouvelle date de début du cours.

## Validation des paramètres : ##

* L'identifiant du cours doit appartenir à la colonne id de la table {course}.
* La date de début du cours cible (startdate) doit être renseignée.
* Le format de la nouvelle date de début doit se conformé aux format supportés par la méthode PHP  DateTime
* La nouvelle date de début doit être différente de la date actuelle de début de cours, si elles sont identiques aucun traitement ne sera réalisé.

## Calcul du déplacement relatif ##

Le déplacement relatif à appliquer aux dates du cours est alors calculé de la façon suivante :

	timeshift = nouvelle_date_debut – ancienne_date_debut

## Liste des dates modifiées si elles étaient renseignées ##
$course->enddate	//date de fin du cours  
Toutes les dates relatives aux restrictions ('availability_date\condition')  
Toutes les dates d'achèvement attendu (completionexpected)  

### Date selon les activités (modules) ###

|  Modules         | Nom des champs date   | Report sur le calendrier étudiant  | 
|---------------- -|-----------------------|------------------------------------| 
| Assign (Devoir)  | duedate allowsubmissionsfromdate  | mod/assign/lib.php                        | 
|	           | gradingduedate cutoffdate         |assign_refresh_events($course->id); | 

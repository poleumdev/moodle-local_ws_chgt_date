# Code #

La structure du plugin est celle d'un service web Moodle.  

Les traitements sont regroupés dans la classe local_wschangedate_external du fichier externallib.php  

La fonction changedates réalise les traitements effectifs.  
Si on souhaite prendre en compte un autre module d'activité, c'est à partir de cette méthode qu'il faudrait agir.  
Si on doit ajouter trop de nouvelles méthodes, on pourra regroupper l'ensemble de nos méthodes 'public static' dans un fichier locallib.php, de sorte à diminuer la complexité
de notre classe.  

# Qualité des développements #
Même si le plugin n'est pas publié sous Moodle.org, il est toujours appréciable de respecter les normes de codage Moodle.  



|  Modules Travis  moodle-plugin-ci | Résultat              |
|-----------------------------------|-----------------------|
| phplint                           | 6 files. No syntax error found |
| phpcpd                            | 0.00% duplicated lines out of 780 total lines of code. |
| phpmd  | FOUND 0 ERRORS AND 10 VIOLATIONS |
| codechecker | (OK) exited with 0.  |
| validate | (OK) exited with 0.  |
| savepoints | (OK) exited with 0.  |
| mustache | No relevant files found to process, free pass! |
| grunt | (OK) exited with 0 |
| phpdoc | (OK) exited with 0 |
| phpunit | No PHPUnit tests to run, free pass! |
| behat | No Behat features to run, free pass! |

En date du 16/12/2020.
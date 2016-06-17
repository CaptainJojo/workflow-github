# Qu’est-ce qu’une bonne Pull Request (PR) ?

- titre de la PR en **français**
- format : label(category): titre
- une description en  **français** de la PR (ce qu’elle doit faire)
- le **cadrage/spécification technique** de la PR
- **comment recetter ?** Connaître le cheminement de recette et les succès pour vérifier la PR
- si la PR contient des **dépendances, il faut les indiquer (via une checklist)**
- commit de message à **l’impératif** : “fixe un bug” et pas “bug fixé”
- **commits sont en anglais** et suive le format label(category): title

# Les labels de workflow

3 labels destinés au workflow : status/wip, status/reviewable, satus/mergeable.
- Une PR avec le label **status/wip** ne doit pas être mergée. Elle est ouverte aux CR.
- Une PR avec le label **status/reviewable** est ouverte aux CR, et doit être marquée satus/mergeable si elle satisfait à 2 CR.
- Une PR avec le label **satus/mergeable** doit être mergée.
- Une PR sans label est reviewable mais pas mergeable (= status/wip, mais moins explicite...).
- Une PR avec le label **status/depend** indique qu’il y a des dépendances externe (peut dépendre d’une autre PR, d’une personne, etc.)

# Les labels pour qualifier le code

- **type/question**: Quand on veut l’avis d’un pair pour un cadrage technique ou fonctionnel, ou lors d’un POC.
- **type/feat**: Quand la PR a un impact pour les utilisateurs (ajout, suppression ou suppression d’une fonctionnalité).
- **type/fix**: Quand la PR corrige un bug.
- **type/chore**: Quand la PR n’a pas d’impact utilisateur (ajout de log, bump de release, modification d’outils internes par exemple).
- **type/refactor**: Quand la PR ne fait que modifier le code mais n’ajoute pas de fonctionnalité ou ne corrige pas de bug.
- **type/test**: Quand la PR ajoute des tests oubliés précédemment.

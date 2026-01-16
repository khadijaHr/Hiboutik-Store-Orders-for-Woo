# Hiboutik Store Orders for WooCommerce

Plugin WordPress qui intègre les commandes magasin Hiboutik dans l'espace client WooCommerce. Affiche automatiquement les commandes passées en magasin physique dans la section « Mon compte », avec détails produits, statuts et points fidélité. L'identification du client se fait via le **numéro de téléphone**.

---

## 📦 Fonctionnalités

- ✅ Connexion à l'API Hiboutik avec authentification sécurisée
- ✅ Recherche automatique du client Hiboutik via son numéro de téléphone
- ✅ Récupération et affichage des commandes Hiboutik associées
- ✅ Affichage détaillé des produits de chaque commande via un système de **popup modal**
- ✅ Pagination automatique des commandes (25 par page)
- ✅ Affichage stylisé et responsive
- ✅ Architecture modulaire avec fichiers CSS et JS séparés
- ✅ Chargement optimisé des assets uniquement sur la page "Mon compte"

---

## 🛠 Installation

### 1. Téléversement du plugin

- Téléchargez ou clonez le plugin dans le dossier `/wp-content/plugins/`
- Créez un dossier nommé `hiboutik-store-orders-woo` (ou le nom de votre choix)
- Placez tous les fichiers du plugin dans ce dossier

### 2. Activation du plugin

- Connectez-vous au tableau de bord WordPress
- Allez dans **Extensions > Extensions installées**
- Trouvez **Hiboutik Store Orders for WooCommerce**
- Cliquez sur **Activer**

### 3. Configuration

Le plugin nécessite la configuration des identifiants API Hiboutik. Vous pouvez les ajouter de deux manières :

#### Option A : Via le code (temporaire pour les tests)
Ajoutez ces lignes dans votre fichier `functions.php` du thème ou dans le plugin :

```php
update_option('hiboutik_user', 'votre_nom_utilisateur');
update_option('hiboutik_key', 'votre_cle_api');
```

#### Option B : Via l'interface WordPress (recommandé)
Créez une page de réglages ou utilisez un plugin de gestion d'options comme **Advanced Custom Fields** ou **Options Framework**.

> 📝 **Note** : Le plugin utilise les fonctions `get_option('hiboutik_user')` et `get_option('hiboutik_key')` pour authentifier chaque requête API.

---

## 🔍 Dépendances & Compatibilité

- **WordPress** : 6.2 ou supérieur
- **WooCommerce** : 4.0 ou supérieur
- **PHP** : 7.0 ou supérieur (recommandé : PHP 7.4+)
- **API Hiboutik** : Compte marchand avec API activée
- **Extension PHP** : cURL (généralement activée par défaut)

---

## ⚙️ Fonctionnement

### 1. Authentification à l'API

Le plugin utilise `cURL` avec authentification HTTP Basic (username:API key) pour se connecter à l'API Hiboutik :

```php
https://mystore.hiboutik.com/api/
```

> ⚠️ **Important** : Remplacez `mystore` par le nom de votre boutique Hiboutik dans le fichier `hiboutik-orders-woo.php` (ligne 74).

### 2. Identification du client

Le client est identifié selon son numéro de téléphone (`$current_user->phone_number`). Le format est automatiquement harmonisé :
- Suppression des espaces
- Normalisation du préfixe international (+212 → 0)
- Comparaison avec les numéros dans Hiboutik

### 3. Récupération des commandes

Une fois le `customers_id` trouvé, le plugin :
1. Récupère les ventes via : `GET /api/customer/{customer_id}/sales/`
2. Pour chaque commande, extrait les détails via : `GET /api/sales/{sale_id}`
3. Filtre les commandes : les commandes avec `sale_ext_ref` non vide sont ignorées (seules les commandes magasin physique sont affichées)

### 4. Affichage dans « Mon compte »

Les commandes sont affichées **avant** le tableau des commandes WooCommerce grâce au hook :

```php
add_action('woocommerce_before_account_orders', 'hiboutik_display_store_orders');
```

Chaque ligne du tableau contient :
- **Numéro de commande** : ID de la vente Hiboutik
- **Date et magasin** : Date de création et nom du magasin
- **Statut** : "En cours" (vert) ou "Validée" (bleu)
- **Total** : Montant avec devise
- **Points** : Points de fidélité gagnés
- **Détails** : Bouton "Voir" pour afficher les produits dans un popup

---

## 🖼 Interface utilisateur

### Popup modal
- Bouton **"Voir"** qui ouvre un popup modal avec les détails complets du panier
- Affichage des produits avec : nom, code-barres, quantité, prix unitaire et total
- Design minimaliste et responsive

### Pagination
- Système de pagination automatique (25 commandes par page)
- Navigation par boutons numérotés
- Style cohérent avec le thème WooCommerce

### Styles
- CSS séparé dans `assets/css/hiboutik-orders.css`
- Facilement personnalisable
- Compatible avec la plupart des thèmes WooCommerce

---

## 🔐 Sécurité

- ✅ Authentification API via username:API key (HTTP Basic Auth)
- ✅ Requêtes sécurisées avec cURL
- ✅ Sanitisation de l'affichage avec `esc_html()` et `esc_attr()`
- ✅ Vérification des permissions utilisateur (`is_user_logged_in()`)
- ✅ Aucune donnée sensible stockée localement
- ✅ Chargement conditionnel des assets (uniquement sur la page "Mon compte")

---

## 🔧 Personnalisation

### Modifier le nombre de commandes par page

Éditez le fichier `assets/js/hiboutik-orders.js` et modifiez la variable :

```javascript
const itemsPerPage = 25; // Changez cette valeur
```

### Modifier l'URL de l'API Hiboutik

Éditez le fichier `hiboutik-orders-woo.php` (ligne 74) :

```php
$baseUrl = "https://votre-boutique.hiboutik.com/api/";
```

### Personnaliser les styles

Éditez le fichier `assets/css/hiboutik-orders.css` pour modifier :
- Les couleurs des boutons
- La taille des popups
- L'espacement des tableaux
- Les styles de pagination

### Changer le champ d'identification

Par défaut, le plugin utilise `$current_user->phone_number`. Pour utiliser l'email à la place, modifiez la fonction `hiboutik_display_store_orders()` dans `hiboutik-orders-woo.php` (lignes 80-98).

---

## 📁 Structure du plugin

```
hiboutik-store-orders-woo/
│
├── assets/
│   ├── css/
│   │   └── hiboutik-orders.css      ← Styles CSS du plugin
│   └── js/
│       └── hiboutik-orders.js       ← Scripts JavaScript (popup + pagination)
│
├── hiboutik-orders-woo.php          ← Fichier principal du plugin
└── README.md                         ← Documentation
```

---

## 🚫 Limitations connues

- ⚠️ Pas de fallback si le numéro de téléphone est manquant ou incorrect
- ⚠️ Ne fonctionne pas avec les comptes clients sans numéro de téléphone valide
- ⚠️ Pas de gestion d'erreur API visible côté client (les erreurs sont silencieuses)
- ⚠️ L'URL de l'API est codée en dur dans le fichier PHP (à personnaliser selon votre boutique)

---

## 🐛 Dépannage

### Les commandes ne s'affichent pas

1. Vérifiez que les identifiants API sont correctement configurés
2. Vérifiez que l'utilisateur a un numéro de téléphone dans son profil WordPress
3. Vérifiez que le numéro de téléphone correspond à un client dans Hiboutik
4. Vérifiez l'URL de l'API dans le fichier PHP

### Le popup ne s'ouvre pas

1. Vérifiez que le fichier JavaScript est bien chargé (inspectez la page avec les outils développeur)
2. Vérifiez la console du navigateur pour d'éventuelles erreurs JavaScript
3. Assurez-vous que jQuery n'est pas en conflit

### Les styles ne s'appliquent pas

1. Vérifiez que le fichier CSS est bien chargé
2. Videz le cache du navigateur et du site (si vous utilisez un plugin de cache)
3. Vérifiez qu'il n'y a pas de conflit avec le thème actif

---

## 📝 Changelog

### Version 1.0.1
- ✅ Séparation des fichiers CSS et JS
- ✅ Utilisation de `wp_enqueue_style()` et `wp_enqueue_script()`
- ✅ Renommage de la fonction principale : `hiboutik_display_store_orders()`
- ✅ Amélioration de la structure du code
- ✅ Chargement conditionnel des assets

---

## 👤 Auteur

**Khadija Har**

- GitHub : [@khadijahr](https://github.com/khadijahr)
- Plugin développé pour l'intégration Hiboutik / WooCommerce

---

## 📄 Licence

Ce plugin est sous licence **GPL v2 ou ultérieure**.

---

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :
- Signaler des bugs
- Proposer des améliorations
- Soumettre des pull requests

---

## 📞 Support

Pour toute question ou problème, veuillez :
1. Vérifier cette documentation
2. Consulter la section Dépannage
3. Ouvrir une issue sur GitHub

---

**Note** : Ce plugin est développé de manière indépendante et n'est pas affilié à Hiboutik ou WooCommerce.

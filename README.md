<p align="center">
    <a href="https://cmstream.zeriix.fr/">
        <img src="./public/img/icons/logo.svg" alt="logo" width="200" height="auto" />
    </a>
</p>
<p align="center">
	<a href="https://cmstream.zeriix.fr/">cmstream.zeriix.fr</a>
</p>

## CMStream - CMS de Streaming

**CMStream** (contraction de CMS et Stream) est un système de gestion de contenu entièrement dédié à la création de plateformes de streaming. Conçu pour permettre à n'importe qui, sans compétences techniques particulières, de créer son propre site de streaming complet - pensez WordPress, mais spécialisé et simplifié pour le streaming.

### Le Défi

Ce projet a été réalisé dans le cadre de notre première année de spécialisation web en école d'ingénieur informatique à l'ESGI, avec des contraintes volontairement extrêmes :

- **Aucune librairie autorisée** (sauf dérogations exceptionnelles)
- **Backend entièrement en PHP natif**
- **Développement sur 5 mois en parallèle des cours et examens**

Face à ces contraintes, nous avons doublé la mise : plutôt que de développer uniquement l'application, nous avons créé **deux frameworks complets de A à Z**.

### Fonctionnalités

#### CMS Complet

- **Système de templates** pour créer des pages personnalisées
- **Constructeur de pages** par composants
- **Gestion multi-sources** pour un même contenu
- **Système de commentaires** intégré
- **Likes sur séries et films**
- **Watchlist, historique, playlist et favoris** pour chaque utilisateur
- **Gestion des utilisateurs** avec inscription par email et validation

#### Dashboard Administrateur

- **Monitoring complet** des utilisateurs et contenus
- **Gestion des permissions** utilisateurs
- **Modération des commentaires**
- **Configuration de l'application**
- **Gestion des pages et templates**
- **Ajout et modification de contenu**

## Stack Technique

### Backend - DupoPHP Framework

Notre framework PHP maison (nommé en référence aux briques Duplo - simples à assembler) :

- **Routeur automatique généré** depuis les commentaires PHP
- ***Système de validation** avancé avec le concept de "Floor" (coffre de données partagé)
- **ORM** custom inspiré de Doctrine
- **Système de migrations**
- **CLI** intégré
- **API RESTful** avec codes de réponse contextualisés

```php
/**
 * @POST{/api/serie}
 * @apiName CreateSerie
 * @apiGroup ContentManager/SerieController
 * @Feature ContentManager
 * @Description Create a serie
 */
class createSerie extends AccessContentsManager
{
    public function checkers(Request $request): array
    {
        return [
            ["type/string", $request->getBody()['title_serie'], "title_serie"],
            ["serie/notexist", ["title" => $request->getBody()['title_serie']]],
            ...
            // Validateurs chaînés via le système Floor
        ];
    }
    
    public function handler(Request $request, Response $response): void
    {
        $serie = Serie::insertOne(
            fn (Serie $seriue) => $s
                ->setTitle($this->floor->pickup("title_serie"))
                ...
        );
        
        $response->info("serie.created")->code(201)->send($serie);
    }
}
```

### Frontend - CuteVue Framework

Notre framework JavaScript maison (nommé en référence à ca ressemble à Vue.js mais plus "cute") :

- **Réactivité des données** avec binding bidirectionnel
- **Système de composants** avec template/script/style
- **Routeur SPA** complet
- **Store global** pour la gestion d'état
- **Client HTTP** avec gestion d'erreurs élégante

```javascript
<div class="max-w-[1130px] min-h-[calc(100vh-60px)] mx-auto mb-[40px] px-7 lg:px-14 flex flex-col justify-center items-center gap-[40px] content">
    <h1 class="text-2xl font-bold">Changement de mot de pass</h1>
    
    <cv-form
    @submit="submit"
    class="w-[500px]"
    >
        <div class="inputs p-[40px] flex flex-col gap-[20px] bg-darkblue">
            <text-input
            type="password" 
            placeholder="Mot de passe"
            cv-model="password"
            :rules="this.passwordRule"
            :always-rule="true"
            class="w-full"
            />

            <text-input
            type="password" 
            placeholder="Confirmez mot de passe"
            cv-model="cpassword"
            :rules="this.cpasswordRule"
            :always-rule="true"
            class="w-full"
            />
            
            <p class="text-center text-[red]" cv-class="{ 'text-[green]': this.success }">{{ this.info }}</p>
        </div>

        <div class="mt-[30px] text-center submit">
            <button 
            type="submit" 
            class="px-[20px] py-[10px] rounded bg-skyblue"
            cv-class="{
                'invisible': this.success === true,
            }"
            >
                Créer un compte
            </button>  
        </div>
    </cv-form>
</div>

<script>
    const [impLoader, impTaob] = await Promise.all([
        import("/public/cuteVue/stores/loader.js"),
        import("/public/cuteVue/taob.js")
    ]);

    const loaderStore = impLoader.loaderStore;
    const taob = impTaob.default;

    export default {
        data: {
            password: "",
            cpassword: "",
            
            info: "",
        },
        computed: {
            cpasswordRule(){
                return [
                    (value) => this.password === this.cpassword || "Les mots de passe conresponde pas.",
                ];
            }
        },
        static: {
            passwordRule: [
                (value) => !!value || "Ce champs est obligatoire.",
                (value) => ...
            ],
        },
        methods: {
            async submit(){
                this.success = false;
                let close = loaderStore.push();

                this.info = "";

                await taob.post("/user/password/validate", {
                    password: this.password,
                    token: router.query.token.replace(/ /g, "+")
                })
                .s(() => {
                    router.push("/");
                })
                .e(() => {
                    this.info = "Lien de changement de mot de passe invalide";
                })
                .result;

                close();
            }
        },
        mounted(){
            ...
        }
    }
</script>

<style>
    ...
</style>
```

## Équipe

- [ZeRiix](https://github.com/ZeRiix) <img style="border-radius: 100%" src="https://avatars.githubusercontent.com/u/70342449?v=4" width="16" alt="ZeRiix"/>
- [Maubry94](https://github.com/Maubry94) <img style="border-radius: 100%" src="https://avatars.githubusercontent.com/u/58041322?v=4" width="16" alt="Maubry94"/>
- [Vitaalx](https://github.com/Vitaalx) <img style="border-radius: 100%" src="https://avatars.githubusercontent.com/u/74609430?v=4" width="16" alt="Vitaalx"/>
- [mathcovax](https://github.com/mathcovax) <img style="border-radius: 100%" src="https://avatars.githubusercontent.com/u/98911237?v=4" width="16" alt="mathcovax"/>

## Installation

### Prérequis

- Docker 
- PostgreSQL (optionnel, config `docker-compose.yml`)
- proxy (optionnel, config `docker-compose.yml`)

### Configuration

1. Clonez le dépôt
2. Lancer `docker compose up` pour démarrer les services
3. Accédez à l'application via `http://localhost:3380`
4. Suivre les instructions à l'écran pour configurer préalablement l'application ainsi que la création de l'administrateur
5. Profitez de l'application !

## Performance

Le site a été testé avec succès jusqu'à **100 utilisateurs simultanés** (est capable de beaucoup plus) et tourne actuellement en production sur serveur dédié.

## Particularités Techniques

### Système Floor (Backend)

Innovation majeure : système de validation en pipeline permettant de chaîner les opérations sur les données et de partager les résultats entre validateurs.

### Optimisation Réseau

Codes de réponse HTTP enrichis avec contexte métier pour économiser la bande passante et améliorer l'UX.

### Architecture Sans Librairies

Développement entièrement natif (exceptions : TailwindCSS en CDN et PHPMailer v5.5 avec dérogation académique).

## Ce qui rend ce projet unique

- **Double framework maison** développés parallèlement à l'application
- **CMS fonctionnel** permettant la création de sites complets
- **Architecture innovante** avec concepts originaux (Floor, génération automatique de routes)

---

_Projet nostalgique et formateur, démontrant qu'avec de la créativité et de la détermination, on peut créer des outils puissants même avec des contraintes importantes._
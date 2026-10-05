# Conception — Rentvly

Artefacts de conception du projet (diagrammes UML, etc.).

## Structure

```
conception/
├── README.md
├── backlog.tex
├── scripts/
│   └── embed-acteur.mjs
└── uml/
    ├── acteur.png
    ├── 02-use-case-visiteur.mmd
    ├── 03-use-case-investisseur.mmd
    ├── 05-sequence-login.mmd / .svg / .png
    ├── 06-sequence-register.mmd / .svg / .png
    ├── 07-sequence-dashboard-investisseur.mmd / .svg / .png
    ├── 08-class-authentification.mmd / .svg / .png
    ├── 09-class-analyse.mmd / .svg / .png
    ├── 09-sequence-estimation-sauvegarde.mmd / .svg / .png
    ├── 10-class-annonces.mmd / .svg / .png
    └── 10-sequence-compte.mmd / .svg / .png
```

## Diagrammes

| Fichier | Description |
|---|---|
| [`02-use-case-visiteur.mmd`](./uml/02-use-case-visiteur.mmd) | Découverte, estimation libre, inscription / connexion |
| [`03-use-case-investisseur.mmd`](./uml/03-use-case-investisseur.mmd) | Dashboard, estimations, achat, ROI, compte |
| [`05-sequence-login.mmd`](./uml/05-sequence-login.mmd) | Séquence login : classique + mot de passe oublié + Google |
| [`06-sequence-register.mmd`](./uml/06-sequence-register.mmd) | Séquence register : formulaire + 2FA e-mail + Google |
| [`07-sequence-dashboard-investisseur.mmd`](./uml/07-sequence-dashboard-investisseur.mmd) | Séquence fusionnée : annonces + dashboard (API backend uniquement) |
| [`08-class-authentification.mmd`](./uml/08-class-authentification.mmd) | Classes auth (User, Sanctum, reset, session, contrôleur) |
| [`09-class-analyse.mmd`](./uml/09-class-analyse.mmd) | Classes d'analyse métier (User, Estimation, Vente, géo) |
| [`09-sequence-estimation-sauvegarde.mmd`](./uml/09-sequence-estimation-sauvegarde.mmd) | Séquence estimation : ML, sauvegarde, sous-estimation |
| [`10-class-annonces.mmd`](./uml/10-class-annonces.mmd) | Classes conception sprint 2 — gestion des annonces |
| [`10-sequence-compte.mmd`](./uml/10-sequence-compte.mmd) | Séquence compte : email, mot de passe, déconnexion |
| [`backlog.tex`](./backlog.tex) | Backlog produit par sprints (annonces + dashboard fusionnés) |

## Backlog LaTeX

Fragment à intégrer dans le rapport (pas un document autonome) :

```latex
\input{figures/backlog}   % ou le chemin adapté dans ton projet Overleaf
```

## Rendu / Overleaf (impression)

Préférer **PNG** ou **PDF** (exporté depuis SVG) :

```latex
\begin{figure}[H]
  \centering
  \includegraphics[width=\textwidth]{figures/08-class-authentification.png}
  \caption{Diagramme de classes — Authentification}
\end{figure}
```

Régénérer :

```bash
npx @mermaid-js/mermaid-cli -i conception/uml/08-class-authentification.mmd -o conception/uml/08-class-authentification.svg -b white --size 2200
npx @mermaid-js/mermaid-cli -i conception/uml/08-class-authentification.mmd -o conception/uml/08-class-authentification.png -b white -s 3 --size 2200
npx @mermaid-js/mermaid-cli -i conception/uml/09-class-analyse.mmd -o conception/uml/09-class-analyse.svg -b white --size 2400
npx @mermaid-js/mermaid-cli -i conception/uml/09-class-analyse.mmd -o conception/uml/09-class-analyse.png -b white -s 3 --size 2400
npx @mermaid-js/mermaid-cli -i conception/uml/10-class-annonces.mmd -o conception/uml/10-class-annonces.svg -b white --size 2200
npx @mermaid-js/mermaid-cli -i conception/uml/10-class-annonces.mmd -o conception/uml/10-class-annonces.png -b white -s 3 --size 2200
npx @mermaid-js/mermaid-cli -i conception/uml/05-sequence-login.mmd -o conception/uml/05-sequence-login.png -b white -s 3 --size 2600 -C conception/uml/sequence-print.css
npx @mermaid-js/mermaid-cli -i conception/uml/06-sequence-register.mmd -o conception/uml/06-sequence-register.png -b white -s 3 --size 2600 -C conception/uml/sequence-print.css
npx @mermaid-js/mermaid-cli -i conception/uml/07-sequence-dashboard-investisseur.mmd -o conception/uml/07-sequence-dashboard-investisseur.png -b white -s 3 --size 2600 -C conception/uml/sequence-print.css
npx @mermaid-js/mermaid-cli -i conception/uml/09-sequence-estimation-sauvegarde.mmd -o conception/uml/09-sequence-estimation-sauvegarde.svg -b white -w 2400 -C conception/uml/sequence-print.css
npx @mermaid-js/mermaid-cli -i conception/uml/10-sequence-compte.mmd -o conception/uml/10-sequence-compte.svg -b white -w 2400 -C conception/uml/sequence-print.css
```

Les acteurs des use cases utilisent `acteur.png` embarqué en **base64**. Si vous modifiez l'image :

```bash
node conception/scripts/embed-acteur.mjs
```

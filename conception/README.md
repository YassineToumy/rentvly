# Conception — Rentvly

Artefacts de conception du projet (diagrammes UML, etc.).

## Structure

```
conception/
├── README.md
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
    ├── 09-sequence-estimation-sauvegarde.mmd / .svg / .png
    └── 10-sequence-compte.mmd / .svg / .png
```

## Diagrammes

| Fichier | Description |
|---|---|
| [`02-use-case-visiteur.mmd`](./uml/02-use-case-visiteur.mmd) | Découverte, estimation libre, inscription / connexion |
| [`03-use-case-investisseur.mmd`](./uml/03-use-case-investisseur.mmd) | Dashboard, estimations, achat, ROI, compte |
| [`05-sequence-login.mmd`](./uml/05-sequence-login.mmd) | Séquence login : classique + mot de passe oublié + Google |
| [`06-sequence-register.mmd`](./uml/06-sequence-register.mmd) | Séquence register : formulaire + 2FA e-mail + Google |
| [`07-sequence-dashboard-investisseur.mmd`](./uml/07-sequence-dashboard-investisseur.mmd) | Séquence dashboard condensée : liste, fiche, achat/ROI, suppression |
| [`08-class-authentification.mmd`](./uml/08-class-authentification.mmd) | Classes auth (User, Sanctum, reset, session, contrôleur) |
| [`09-sequence-estimation-sauvegarde.mmd`](./uml/09-sequence-estimation-sauvegarde.mmd) | Séquence estimation : ML, sauvegarde, sous-estimation |
| [`10-sequence-compte.mmd`](./uml/10-sequence-compte.mmd) | Séquence compte : email, mot de passe, déconnexion |

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
npx @mermaid-js/mermaid-cli -i conception/uml/07-sequence-dashboard-investisseur.mmd -o conception/uml/07-sequence-dashboard-investisseur.svg -b white -w 2400 -C conception/uml/sequence-print.css
npx @mermaid-js/mermaid-cli -i conception/uml/08-class-authentification.mmd -o conception/uml/08-class-authentification.svg -b white -w 2200
npx @mermaid-js/mermaid-cli -i conception/uml/09-sequence-estimation-sauvegarde.mmd -o conception/uml/09-sequence-estimation-sauvegarde.svg -b white -w 2400 -C conception/uml/sequence-print.css
npx @mermaid-js/mermaid-cli -i conception/uml/10-sequence-compte.mmd -o conception/uml/10-sequence-compte.svg -b white -w 2400 -C conception/uml/sequence-print.css
```

Les acteurs des use cases utilisent `acteur.png` embarqué en **base64**. Si vous modifiez l'image :

```bash
node conception/scripts/embed-acteur.mjs
```

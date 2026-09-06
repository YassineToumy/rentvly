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
    └── 08-class-authentification.mmd / .svg / .png
```

## Diagrammes

| Fichier | Description |
|---|---|
| [`02-use-case-visiteur.mmd`](./uml/02-use-case-visiteur.mmd) | Découverte, estimation libre, inscription / connexion |
| [`03-use-case-investisseur.mmd`](./uml/03-use-case-investisseur.mmd) | Dashboard, estimations, achat, ROI, compte |
| [`05-sequence-login.mmd`](./uml/05-sequence-login.mmd) | Séquence login : classique + mot de passe oublié + Google |
| [`06-sequence-register.mmd`](./uml/06-sequence-register.mmd) | Séquence register : formulaire + 2FA e-mail + Google |
| [`08-class-authentification.mmd`](./uml/08-class-authentification.mmd) | Classes auth (User, Sanctum, reset, session, contrôleur) |

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
npx @mermaid-js/mermaid-cli -i conception/uml/08-class-authentification.mmd -o conception/uml/08-class-authentification.svg -b white -w 2200
npx @mermaid-js/mermaid-cli -i conception/uml/08-class-authentification.mmd -o conception/uml/08-class-authentification.png -b white -s 3 -w 2200
```

Les acteurs des use cases utilisent `acteur.png` embarqué en **base64**. Si vous modifiez l'image :

```bash
node conception/scripts/embed-acteur.mjs
```

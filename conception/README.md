# Conception — Rentvly

Artefacts de conception du projet (diagrammes UML, etc.).

## Structure

```
conception/
├── README.md
└── uml/
    ├── acteur.png
    ├── 02-use-case-visiteur.mmd
    └── 03-use-case-investisseur.mmd
```

## Diagrammes

| Fichier | Description |
|---|---|
| [`02-use-case-visiteur.mmd`](./uml/02-use-case-visiteur.mmd) | Découverte, estimation libre, inscription / connexion |
| [`03-use-case-investisseur.mmd`](./uml/03-use-case-investisseur.mmd) | Dashboard, estimations, achat, ROI, compte |

## Rendu

Ouvrir le `.mmd` dans VS Code ou [mermaid.live](https://mermaid.live), puis exporter en SVG / PNG.

Les acteurs utilisent `acteur.png` embarqué en **base64** dans le `.mmd` (Mermaid ne lit pas les fichiers locaux). Si vous modifiez `acteur.png` :

```bash
node conception/scripts/embed-acteur.mjs
```

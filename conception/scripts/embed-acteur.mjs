// Réintègre acteur.png (base64) dans les .mmd après modification de l'image.
// Usage : node conception/scripts/embed-acteur.mjs

import { readFileSync, writeFileSync } from 'node:fs'

const uri =
  'data:image/png;base64,' +
  readFileSync('conception/uml/acteur.png').toString('base64')

const patches = [
  {
    file: 'conception/uml/02-use-case-visiteur.mmd',
    re: /    Visiteur@\{[^\n]+\}/,
    line: `    Visiteur@{ img: "${uri}", label: "Visiteur", w: 45, h: 95, pos: "b", constraint: "off" }`,
  },
  {
    file: 'conception/uml/03-use-case-investisseur.mmd',
    re: /    Investisseur@\{[^\n]+\}/,
    line: `    Investisseur@{ img: "${uri}", label: "Investisseur", w: 45, h: 95, pos: "b", constraint: "off" }`,
  },
]

for (const { file, re, line } of patches) {
  writeFileSync(file, readFileSync(file, 'utf8').replace(re, line))
  console.log('✓', file)
}

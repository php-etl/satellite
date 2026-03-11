# Fixtures workflow (format nodes/edges)

Ces fixtures reprennent les exemples du document [GUIDE-MIGRATION-COMPILATEUR-V1](../../../../documentation/requirements/GUIDE-MIGRATION-COMPILATEUR-V1.md).

| Fichier | Description |
|---------|-------------|
| `lineaire.yaml` | Chaîne linéaire : input → n1 → n2 → n3 → output |
| `dag.yaml` | DAG avec cas Fork (n1 → n2, n3) et cas Merge (n2, n3 → output) |
| `pipeline-standalone.yaml` | Format déprécié `satellite.pipeline` — pour migration via `migrate:pipeline-to-workflow` |

Usage prévu : snapshot testing du générateur workflow Temporal.

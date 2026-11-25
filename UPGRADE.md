# Upgrade

## 1.0 → 1.1

- Column settings of legacy content elements (Contao 5.3: `form`, `accordionSingle`, `accordionStart`, `sliderStart`)
  are rendered again, as they were with `kiwi/contao-bootstrap-bundle`. Under 1.0 they had no effect. Remedy: check
  these elements for column settings that were never removed, and remove them where the layout relies on 1.0's output.

# Fotografías de los platos

Este directorio es donde van las fotos reales de los platos. El menú las
busca por nombre y las usa apenas existen, sin tocar la base de datos.

## Cómo se nombran

El archivo se llama como el plato, en minúsculas, con guiones y la extensión que
sea. Por ejemplo, para el plato `Pizza Margarita`:

```
public/platos/pizza-margarita.webp
```

Se aceptan `.avif`, `.webp`, `.jpg`, `.jpeg`, `.png` y `.svg`, y gana la primera
que exista en ese orden, así que un `.jpg` se puede mejorar después a `.webp` sin
tocar el código.

La portada del menú se reemplaza poniendo el archivo en:

```
public/platos/hero.jpg
```

## Formato recomendado

- Horizontal, relación `4:3` o `16:10`.
- Ancho mínimo de 1200 px.
- `.webp` o `.avif` para que la carta siga siendo rápida.
- Sin texto incrustado, sin logos ni marcas de agua.

## Mientras no haya fotos

Si un plato no tiene archivo, la carta muestra una escena ilustrada de su
categoría, guardada en `public/img/menu`. Son recursos provisionales pensados
para que el menú se vea termin mientras llegan las fotografías de la cocina.

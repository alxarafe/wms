# API de creación y listado de familias

> Estado: implementado en PHP sobre wms_review_v2. Java queda aplazado.

## Contrato común

PHP expone POST /api/item-families y GET /api/item-families con JSON.

El cuerpo de alta contiene code, name y attributes. attributes es obligatorio y
puede ser []. Cada elemento es un código existente de storage_attribute, por ejemplo `FOOD` o
`CHILLED`; el UUID queda reservado para la persistencia interna. La respuesta de alta es 201 con id UUID v7, code, name y la
lista de códigos en attributes.

Un código de familia duplicado devuelve 409. JSON mal formado, campos inválidos,
código de atributo inválido devuelve 422 y una referencia inexistente devuelve 404,
con un objeto error. El código y el nombre no pueden estar vacíos, el nombre tiene un máximo
de 255 caracteres y la lista no admite códigos repetidos.

En el esquema v2 los atributos son vínculos hacia storage_attribute mediante
family_storage_attribute. La familia y sus vínculos se guardan en una
transacción; el repositorio vuelve a comprobar las referencias dentro de ella.

## Listado

GET /api/item-families devuelve 200 con un array. Cada elemento contiene id,
code, name y attributes con los códigos vinculados, ordenados. Las familias se
ordenan por code ascendente.

## Pruebas

bin/bruno_families_test.sh comprueba la creación HTTP de familias con y sin
atributos, además de la persistencia de sus vínculos.

Las colecciones se ejecutan actualmente solo contra PHP. Java queda pendiente de
adaptación al catálogo v2.

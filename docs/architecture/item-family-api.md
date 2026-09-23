# API de creación y listado de familias

> Estado: implementado en PHP sobre wms_review_v2. Java queda aplazado.

## Contrato común

PHP expone POST /api/item-families y GET /api/item-families con JSON.

El cuerpo de alta contiene code, name y attributes. attributes es obligatorio y
puede ser []. Cada elemento es un UUID existente de storage_attribute; no es un
código de atributo. La respuesta de alta es 201 con id UUID v7, code, name y la
lista de UUID en attributes.

Un código de familia duplicado devuelve 409. JSON mal formado, campos inválidos,
UUID de atributo inválido o referencia inexistente devuelven 422 con un objeto
error. El código y el nombre no pueden estar vacíos, el nombre tiene un máximo
de 255 caracteres y la lista no admite UUID repetidos.

En el esquema v2 los atributos son vínculos hacia storage_attribute mediante
family_storage_attribute. La familia y sus vínculos se guardan en una
transacción; el repositorio vuelve a comprobar las referencias dentro de ella.

## Listado

GET /api/item-families devuelve 200 con un array. Cada elemento contiene id,
code, name y attributes con los UUID vinculados, ordenados. Las familias se
ordenan por code ascendente.

## Pruebas

bin/bruno_families_test.sh comprueba familias sin atributos. La creación y
lectura del catálogo por HTTP queda pendiente de incorporar a las colecciones
Bruno; las pruebas de persistencia PHP cubren actualmente ese recorrido.

Las colecciones se ejecutan actualmente solo contra PHP. Java queda pendiente de
adaptación al catálogo v2.

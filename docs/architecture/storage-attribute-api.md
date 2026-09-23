# API de atributos de almacenamiento

Estado: implementado en PHP sobre wms_review_v2. Java queda aplazado.

PHP expone POST /api/storage-attributes para crear, GET /api/storage-attributes
para listar y GET /api/storage-attributes/{id} para consultar por UUID.

El alta recibe code, name y exclusive_group_code opcional. Los códigos se
normalizan a mayúsculas, tienen entre 1 y 30 caracteres y rechazan los códigos
históricos IS_FOOD, IS_CHEMICAL, IS_REFRIGERATED e IS_FROZEN. La migración 007
normaliza esos valores existentes a FOOD, CHEMICAL, CHILLED y FROZEN,
conservando UUID y vínculos.

La respuesta contiene id, code, name y exclusive_group_code. Un alta correcta
devuelve 201; consultas y listados devuelven 200; un duplicado devuelve 409; un
UUID inexistente devuelve 404; JSON o campos inválidos devuelven 422.

POST /api/item-families usa UUID del catálogo en attributes. La familia y sus
vínculos se guardan en una transacción y el repositorio revalida las referencias.

La solución es deliberadamente sencilla: un atributo tiene código, nombre y
grupo opcional. El grupo todavía no aplica reglas de exclusividad, y no se
implementan bajas ni edición.

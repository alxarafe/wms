# Familias de artículo iniciales

## Estado implementado

Las familias se relacionan con atributos del catálogo
wms_review_v2.storage_attribute mediante family_storage_attribute. La API
recibe códigos de atributos en attributes y no crea atributos implícitamente.

Los códigos históricos IS_FOOD, IS_CHILLED, IS_FROZEN e IS_CHEMICAL no
forman parte del contrato vigente. La migración 007 los transforma,
respectivamente, en FOOD, CHILLED, FROZEN y CHEMICAL, conservando UUID y
vínculos. El catálogo actual usa esos códigos neutrales.

Un código de atributo inválido se rechaza con 422 y una referencia inexistente con 404.

## Límites y evolución

La implementación permite varios atributos por familia y un grupo opcional en
cada atributo. El grupo todavía no aplica reglas de exclusividad al crear
familias ni al mover stock.

No se implementan bajas, edición, herencia de atributos ni validación de
compatibilidad. Son extensiones aplazadas que pueden reutilizar los UUID y la
tabla de vínculos actuales.

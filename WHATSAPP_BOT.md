# Bot de WhatsApp en PHP

## Archivos

- `whatsapp_webhook.php`: webhook para verificar con Meta, recibir mensajes y responder.
- `whatsapp_config.php`: configuracion local del bot.
- `.gitignore`: excluye `whatsapp_config.php` del repositorio.

## Configuracion

Edita `whatsapp_config.php` y completa estos valores:

- `verify_token`
- `access_token`
- `phone_number_id`

Tambien puedes usar variables de entorno:

- `WHATSAPP_VERIFY_TOKEN`
- `WHATSAPP_ACCESS_TOKEN`
- `WHATSAPP_PHONE_NUMBER_ID`

## URL del webhook

Si trabajas en XAMPP local, la ruta es:

`http://localhost/Mokumba/whatsapp_webhook.php`

Para Meta necesitas una URL publica con HTTPS. Para pruebas puedes usar un tunel como `ngrok`.

## Verificacion en Meta

En la configuracion del webhook de Meta usa:

- Callback URL: tu URL publica de `whatsapp_webhook.php`
- Verify token: el mismo `verify_token` configurado en PHP

## Respuestas actuales

El bot responde a estas palabras clave:

- `hola`
- `buenas`
- `menu`
- `menú`
- `contratacion`
- `contrataciones`
- `disponibilidad`
- `evento`
- `eventos`
- `premio`
- `premios`
- `integrante`
- `integrantes`
- `redes`
- `instagram`
- `facebook`
- `contacto`
- `ayuda`

## Validacion

Puedes validar la sintaxis con el PHP de XAMPP:

`C:\xampp\php\php.exe -l whatsapp_webhook.php`

## Siguiente paso recomendado

Definir respuestas nuevas para:

- tarifas
- tipos de show
- ciudades
- tiempo de respuesta humana
- derivacion a una persona real

# Ask the Experts — Blog REST API (integrazione ChatGPT)

L'endpoint `https://www.allonwheel.com/api/blog.php` permette a ChatGPT (Custom
GPT / Actions) o a qualsiasi client di **creare, aggiornare, listare ed
eliminare** gli articoli dell'hub editoriale, in **bozza**, **programmati** o
**pubblicati direttamente**.

## Autenticazione
Header su ogni richiesta:
```
Authorization: Bearer <BLOG_API_KEY>
```
(oppure `X-Api-Key: <BLOG_API_KEY>`). La chiave sta nel `.env` fuori webroot
(`BLOG_API_KEY`). Senza chiave configurata l'API risponde `503`.

## Campi articolo (JSON)
| Campo | Tipo | Note |
|---|---|---|
| `title` | string | **obbligatorio** |
| `body` | string | **obbligatorio** — la Expert Answer (paragrafi separati da riga vuota) |
| `question` | string | la domanda dell'utente ("Ask the Experts") |
| `excerpt` | string | sommario breve |
| `outlines` | array/string | scaletta / punti chiave (una voce per riga) |
| `faq` | array | `[{"q":"...","a":"..."}]` → genera lo schema FAQPage |
| `category` | string | slug: `technical-design`, `feasibility`, `costs`, `registration` |
| `image` | string | filename in `/upload_image/blog/original/` (facoltativo) |
| `slug` | string | SEO; se assente e' derivato dal titolo |
| `status` | string | `draft` \| `pending` \| `scheduled` \| `published` |
| `published_at` | string | `YYYY-MM-DD HH:MM:SS` — richiesto per `scheduled` |

## Endpoint
| Metodo | URL | Azione |
|---|---|---|
| GET | `/api/blog.php?meta=1` | categorie + stati + campi ammessi |
| GET | `/api/blog.php?status=draft` | elenca le bozze |
| GET | `/api/blog.php?id=123` \| `?slug=...` | singolo articolo |
| POST | `/api/blog.php` | crea (body JSON) |
| PUT | `/api/blog.php?id=123` | aggiorna |
| DELETE | `/api/blog.php?id=123` | elimina |

Ambienti solo-POST: passare `{"_method":"PUT"}` o `?_method=PUT`.

## Esempi
Creare una **bozza**:
```bash
curl -X POST https://www.allonwheel.com/api/blog.php \
  -H "Authorization: Bearer $BLOG_API_KEY" -H "Content-Type: application/json" \
  -d '{"title":"EU type-approval for mobile clinics",
       "question":"Do I need EU type-approval for a mobile clinic?",
       "category":"registration",
       "body":"Short answer: yes.\n\nHere is the full picture...",
       "outlines":["What approval means","Which category applies","Timeline"],
       "faq":[{"q":"How long does it take?","a":"Typically 6-12 weeks."}],
       "status":"draft"}'
```
**Pubblicare** subito: `"status":"published"`.
**Programmare**: `"status":"scheduled","published_at":"2026-08-10 09:00:00"`
(il cron `cron/blog_publish_scheduled.php` la pubblichera' all'orario indicato).

## Schema OpenAPI 3.1 (per una Action di un Custom GPT)
Incolla questo schema nella sezione *Actions* del GPT; imposta l'autenticazione
su **API Key → Bearer** con la tua `BLOG_API_KEY`.

```yaml
openapi: 3.1.0
info:
  title: All on Wheel — Blog API
  version: "1.0"
servers:
  - url: https://www.allonwheel.com
paths:
  /api/blog.php:
    get:
      operationId: listOrGetArticles
      summary: List articles or fetch one (by id/slug), or meta.
      parameters:
        - { name: id, in: query, schema: { type: integer } }
        - { name: slug, in: query, schema: { type: string } }
        - { name: category, in: query, schema: { type: string } }
        - { name: status, in: query, schema: { type: string } }
        - { name: meta, in: query, schema: { type: integer } }
        - { name: limit, in: query, schema: { type: integer } }
        - { name: offset, in: query, schema: { type: integer } }
      responses: { "200": { description: OK } }
    post:
      operationId: createArticle
      summary: Create an article (draft, scheduled or published).
      requestBody:
        required: true
        content:
          application/json:
            schema: { $ref: "#/components/schemas/Article" }
      responses: { "201": { description: Created } }
    put:
      operationId: updateArticle
      summary: Update an article by id.
      parameters:
        - { name: id, in: query, required: true, schema: { type: integer } }
      requestBody:
        content:
          application/json:
            schema: { $ref: "#/components/schemas/Article" }
      responses: { "200": { description: Updated } }
    delete:
      operationId: deleteArticle
      summary: Delete an article by id.
      parameters:
        - { name: id, in: query, required: true, schema: { type: integer } }
      responses: { "200": { description: Deleted } }
components:
  securitySchemes:
    bearerAuth: { type: http, scheme: bearer }
  schemas:
    Article:
      type: object
      required: [title, body]
      properties:
        title: { type: string }
        body: { type: string, description: "Expert answer" }
        question: { type: string }
        excerpt: { type: string }
        outlines: { type: array, items: { type: string } }
        faq:
          type: array
          items:
            type: object
            properties: { q: { type: string }, a: { type: string } }
        category: { type: string, enum: [technical-design, feasibility, costs, registration] }
        image: { type: string }
        slug: { type: string }
        status: { type: string, enum: [draft, pending, scheduled, published] }
        published_at: { type: string }
security:
  - bearerAuth: []
```

## Prompt d'esempio per il GPT
> "Write a 700-word expert answer for the *Costs* category titled *Buy vs rent:
> total cost of ownership for a race trailer*. Include a 4-point outline and 3
> FAQ. Save it as a **draft**."

> "Publish article id 12 now."

> "Schedule this hospitality-unit insulation guide for next Monday at 9am."

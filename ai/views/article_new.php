<?php
declare(strict_types=1);
?>

<div class="post_box">

<h2>AI Article Generator</h2>

<form
    id="aiArticleForm"
    method="post"
    autocomplete="off"
>

<table width="100%">

<tr>

<td width="220">

Topic

</td>

<td>

<input
type="text"
name="topic"
id="topic"
class="input_field"
value="<?= htmlspecialchars($form['topic']); ?>"
>

</td>

</tr>

<tr>

<td>

Keywords

</td>

<td>

<input
type="text"
name="keywords"
id="keywords"
class="input_field"
value="<?= htmlspecialchars($form['keywords']); ?>"
>

</td>

</tr>

<tr>

<td>

Category

</td>

<td>

<input
type="text"
name="category"
id="category"
class="input_field"
value="<?= htmlspecialchars($form['category']); ?>"
>

</td>

</tr>

<tr>

<td>

Audience

</td>

<td>

<input
type="text"
name="audience"
id="audience"
class="input_field"
value="<?= htmlspecialchars($form['audience']); ?>"
>

</td>

</tr>

<tr>

<td>

Country

</td>

<td>

<input
type="text"
name="country"
id="country"
class="input_field"
value="<?= htmlspecialchars($form['country']); ?>"
>

</td>

</tr>

<tr>

<td>

Language

</td>

<td>

<select
name="language"
id="language"
>

<option>English</option>

<option>Italian</option>

<option>French</option>

<option>German</option>

<option>Spanish</option>

</select>

</td>

</tr>

<tr>

<td>

Tone

</td>

<td>

<select
name="tone"
id="tone"
>

<option>Professional</option>

<option>Technical</option>

<option>Commercial</option>

<option>Journalistic</option>

<option>Educational</option>

</select>

</td>

</tr>

<tr>

<td>

Length

</td>

<td>

<select
name="length"
id="length"
>

<option>Short</option>

<option selected>Medium</option>

<option>Long</option>

<option>Very Long</option>

</select>

</td>

</tr>

<tr>

<td>

Purpose

</td>

<td>

<input
type="text"
name="purpose"
id="purpose"
class="input_field"
value="<?= htmlspecialchars($form['purpose']); ?>"
>

</td>

</tr>

<tr>

<td>

Reference URLs

</td>

<td>

<textarea
id="references"
name="references"
rows="5"
><?= htmlspecialchars($form['references']); ?></textarea>

</td>

</tr>

</table>

<div class="cleaner h20"></div>

<button
type="button"
id="generateArticle"
class="more"
>

Generate Article

</button>

</form>

</div>

<div
id="generationResult"
style="display:none;"
>

</div>
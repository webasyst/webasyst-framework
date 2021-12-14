<?php

return array(
    'themes' => true,
    'vars' => array(
        'page.html' => array(
            '$page.name' => _w('Page name'),
            '$page.title' => _w('Page title (&lt;title&gt;)'),
            '$page.content' => _w('Page content'),
            '$page.update_datetime' => _w('Page last update datetime'),
        ),
        '$wa' => array(
            '$wa->site->pages()' => _w('Returns the array of pages associated with the current “Site” app settlement. Each page is an array of ( <em>"id", "name", "title", "url", "create_datetime", "update_datetime", "content"[, "custom_1", "custom_2", …]</em> )'),
            //'$wa->site->pages(false)' => _w('Page list (only id, name, title, url, create_datetime, update_datetime)'),
            '$wa->site->page(<em>id</em>)' => _w('Returns page info (array) by <em>id</em> (int)'),
        ),
        'index.html' => array(
            '$content' => _w('Content'),
            '$meta_keywords' => _w('META Keywords'),
            '$meta_description' => _w('META Description'),
        ),
        'error.html' => array(
            '$error_code' => _w('Error code (e.g. 404)'),
            '$error_message' => _w('Error message'),
        )
    ),
    'blocks' => array(
        'send_email_form' => array(
            'description' => _w('Email feedback form'),
            'content' => '
<style>
    .wa-form { float: left; margin: 10px 0; overflow: visible; }
    .wa-form .wa-field { clear: left; margin: 0; padding-top: 3px; }
    .wa-form .wa-field .wa-name { float: left; width: 155px; padding-top: 0.05em; padding-bottom: 10px; font-size: 0.95em; }
    .wa-form .wa-field .wa-value { margin-left: 180px; margin-bottom: 5px; position: relative; }
    .wa-form .wa-field .wa-value.wa-submit { margin-top: 0; }
    .wa-form .wa-field .wa-value input[type="text"], .wa-form .wa-field .wa-value input[type="email"], .wa-form .wa-field .wa-value input[type="password"] { width: 30%; min-width: 200px; margin: 0; }
    .wa-form .wa-field .wa-value textarea { min-width: 300px; height: 70px; }
    input, textarea { font-size: 1em; color: black; font-family: "Georgia", Times, serif; }
    .wa-form .wa-captcha { padding: 7px 0 10px; }
    .wa-form .wa-captcha p { clear: left; margin: 0; }
    .wa-captcha img { float: left; margin-right: 5px; margin-top: -8px; }
    .wa-captcha .wa-captcha-refresh { color: #AAAAAA; font-size: 0.8em; text-decoration: underline; }

    @media (max-width: 760px) {
        .wa-form .wa-field:not(:first-child) { padding: 0; }
        .wa-form .wa-field:not(:first-child) { margin-top: 1rem; }
        .wa-form .wa-field .wa-name { float: none; width: auto; margin: 0; padding: 0; }
        .wa-form .wa-field .wa-value { float: none; width: auto; margin: .5rem 0 0; padding: 0; }
    }
</style>
{$errors = array()}
{if $wa->post("send") and $wa->sendEmail("", $errors)}
<h1>[s`Thank you!`]</h1>
<p>[s`Your message has been sent.`]</p>
{else}
<div class="wa-form">
  <form method="post" action="">
  <div class="wa-field">
    <div class="wa-name">[s`Name`]:</div>
    <div class="wa-value"><input name="name" type="text" value="{$wa->post("name")|escape}"></div>
  </div>
  <div class="wa-field">
    <div class="wa-name">[s`Email`]:</div>
    <div class="wa-value">
      <input {if !empty($errors.email)}class="wa-error"{/if} name="email" type="email" value="{$wa->post("email")|escape}" >
      {if !empty($errors.email)}<em class="wa-error-msg">{$errors.email}</em>{/if}
    </div>
  </div>
  <div class="wa-field">
    <div class="wa-name">[s`Message`]:</div>
    <div class="wa-value">
      <input type="hidden" name="subject" value="[s`Request from website`]">
      <textarea {if !empty($errors.body)}class="wa-error"{/if} name="body">{$wa->post("body")|escape}</textarea>
      {if !empty($errors.body)}<em class="wa-error-msg">{$errors.body}</em>{/if}
    </div>
  </div>
  <div class="wa-field">
    <div class="wa-value">
        {$wa->captcha(!empty($errors.captcha))}
        {if !empty($errors.captcha)}<em class="wa-error-msg">{$errors.captcha}</em>{/if}
    </div>
  </div>

  {$agreement_link = ""}
  {if $agreement_link}
    <div class="wa-field">
      <div class="wa-value">
        <input type="hidden" name="agree_to_terms" value="">
        <label>
          <input type="checkbox" name="agree_to_terms" value="1"{if $wa->post("agree_to_terms")} checked{/if}>
          [s`I agree to the`] <a href="{$agreement_link|escape}" target="_blank">[s`personal data protection policy`]</a>
          {if !empty($errors.agree_to_terms)}<em class="wa-error-msg">{$errors.agree_to_terms}</em>{/if}
        </label>
      </div>
    </div>
  {/if}

  <div class="wa-field">
    <div class="wa-value wa-submit">
      {if !empty($errors.all)}<em class="wa-error-msg">{$errors.all}</em><br>{/if}
      <input type="submit" value="[s`Send`]" name="send">
    </div>
  </div>
  </form>
</div>
{/if}')
    )
);
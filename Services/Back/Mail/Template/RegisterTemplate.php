<?php

namespace Services\Back\Mail\Template;

use Services\Back\Mail\Template\BaseTemplate;

class RegisterTemplate extends BaseTemplate
{
    private string $fullname;

    private string $validateUrl;

    public function __construct(string $fullname, string $validateUrl)
    {
        $this->fullname = $fullname;
        $this->validateUrl = $validateUrl;
    }

    public static function create(string $username, string $validateUrl): self
    {
        return new self($username, $validateUrl);
    }

    public function render(): string 
    {
        $appName = CONFIG["APP_NAME"];

        return self::wrapContent(
            <<<HTML
                <h1 style="font-family: Arial, sans-serif; color: #DADADA; font-size: 24px; margin: 0 0 36px 0">Bienvenue sur {$appName} !</h1>
                <p style="font-family: Arial, sans-serif; color: #DADADA; font-size: 16px; margin: 0 0 24px 0">
                    Bonjour {$this->fullname}. Merci de vous être inscrit(e).<br>
                    <br>
                    Prêt(e) à vivre une expérience incroyable et partager votre avis sur {$appName} ?<br>
                    Votre aventure commence ici !
                </p>
                <a href="{$this->validateUrl}" style="display: inline-block; background-color: #5499C7; color: #fff; text-decoration: none; font-family: Arial, sans-serif; font-size: 16px; padding: 12px 32px; border-radius: 4px; margin-bottom: 24px;">
                    Valider mon compte
                </a>
                <p style="font-family: Arial, sans-serif; color: #6c757d; font-size: 13px; margin: 24px 0 0 0">
                    Si vous n’êtes pas à l’origine de cette inscription, ignorez simplement cet email.
                </p>
            HTML
        );
    }
}
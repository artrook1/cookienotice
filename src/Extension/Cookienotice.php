<?php

/**
 * @package     plg_system_cookienotice
 * @copyright   (C) 2026 artrook1
 * @license     GNU General Public License version 2 or later
 */

namespace Joomla\Plugin\System\Cookienotice\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Event\Application\AfterRenderEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\SubscriberInterface;

/**
 * Simple cookie notice banner for any Joomla site (Joomla 5/6).
 * Keeps the cookieAcceptanceCookie name for compatibility with common EU cookie plugins.
 */
final class Cookienotice extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterRender' => 'onAfterRender',
        ];
    }

    public function onAfterRender(AfterRenderEvent $event): void
    {
        $app = $event->getApplication();

        if (!$app->isClient('site')) {
            return;
        }

        $document = $app->getDocument();

        if ($document->getType() !== 'html') {
            return;
        }

        // Same cookie name as EUCookieDirectiveLite / legacy BTL cookie plugin.
        if (($app->getInput()->cookie->getString('cookieAcceptanceCookie', '') === 'accepted')) {
            return;
        }

        if ($app->getInput()->getCmd('tmpl') === 'component') {
            return;
        }

        $body = $app->getBody();

        if ($body === '' || stripos($body, '</body>') === false) {
            return;
        }

        $message = trim((string) $this->params->get(
            'warningMessage',
            "Important Cookie Information - this message will appear only once.\n\n"
            . "To give you the best possible experience, this site uses cookies and by continuing to use the site you agree that we can save them on your device.\n\n"
            . 'Cookies are small text files which are placed on your computer and allow to remember your preferences and some other details of your visit to our site.'
        ));

        $detailsUrl = trim((string) $this->params->get('detailsUrl', 'privacy-policy'));

        if ($detailsUrl === '' || $detailsUrl === 'index.php?') {
            $detailsUrl = 'privacy-policy';
        }

        if (!preg_match('#^https?://#i', $detailsUrl)) {
            $detailsUrl = Uri::root() . ltrim($detailsUrl, '/');
        }

        $acceptLabel = trim((string) $this->params->get('acceptLabel', 'I accept cookies from this site.'));
        $agreeButton = trim((string) $this->params->get('agreeButton', 'Agree'));

        if ($acceptLabel === '') {
            $acceptLabel = 'I accept cookies from this site.';
        }

        if ($agreeButton === '') {
            $agreeButton = 'Agree';
        }

        $messageHtml = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        $privacyHref = htmlspecialchars($detailsUrl, ENT_QUOTES, 'UTF-8');
        $acceptHtml  = htmlspecialchars($acceptLabel, ENT_QUOTES, 'UTF-8');
        $agreeHtml   = htmlspecialchars($agreeButton, ENT_QUOTES, 'UTF-8');

        $banner = <<<HTML
<style id="cookie-notice-style">
#cookie-notice-banner{position:fixed;z-index:99999;top:0;left:0;right:0;background:#1d1d1d;color:#fff;font:normal 13px/1.45 Arial,Verdana,sans-serif;box-shadow:0 2px 10px rgba(0,0,0,.35)}
#cookie-notice-banner .cookie-notice-inner{max-width:1100px;margin:0 auto;padding:14px 16px 16px}
#cookie-notice-banner p{margin:0 0 .65rem;color:#fff}
#cookie-notice-banner a{color:#fff;text-decoration:underline}
#cookie-notice-banner .cookie-notice-actions{display:flex;flex-wrap:wrap;align-items:center;gap:.75rem 1rem;margin-top:.5rem}
#cookie-notice-banner .cookie-notice-accept-label{color:#fff}
#cookie-notice-banner .cookie-notice-agree{appearance:none;border:0;cursor:pointer;background:#e8e8e8;color:#111;font-weight:700;padding:.45rem 1.1rem;border-radius:2px}
#cookie-notice-banner .cookie-notice-agree:hover,#cookie-notice-banner .cookie-notice-agree:focus{background:#fff}
</style>
<div id="cookie-notice-banner" role="dialog" aria-label="Cookie information">
	<div class="cookie-notice-inner">
		<div class="cookie-notice-text">
			<p>{$messageHtml} To find out more about the cookies we use see our <a id="cookieMessageDetailsLink" title="View our privacy policy page" href="{$privacyHref}">privacy policy</a>.</p>
		</div>
		<div class="cookie-notice-actions">
			<span class="cookie-notice-accept-label">{$acceptHtml}</span>
			<button type="button" class="cookie-notice-agree" id="cookie-notice-agree">{$agreeHtml}</button>
		</div>
	</div>
</div>
<script>
(function(){
	var btn=document.getElementById('cookie-notice-agree');
	var box=document.getElementById('cookie-notice-banner');
	if(!btn||!box){return;}
	btn.addEventListener('click',function(){
		var maxAge=60*60*24*365*10;
		document.cookie='cookieAcceptanceCookie=accepted;path=/;max-age='+maxAge+';SameSite=Lax';
		box.parentNode.removeChild(box);
		var st=document.getElementById('cookie-notice-style');
		if(st){st.parentNode.removeChild(st);}
	});
})();
</script>
HTML;

        $app->setBody(str_ireplace('</body>', $banner . '</body>', $body));
    }
}

��    
      l      �       �   4   �   3   &     Z  -  g     �     �     �  �  �     �    �  �   �  �   1     �  ~  �      c	  (   �	  P   �	  i  �	     h                  
      	                     Allows backend users to subscribe for blogs by email Cron is working properly. Latest execution time: %s Email alerts Email alerts require CRON to be properly setup for Webasyst Blog app, which is currently not the case. Setup cron for the Blog app with the following command (<a href="http://www.webasyst.com/ru/framework/docs/server/cron/" target="_blank">cron setup instructions</a>):<br /><br /> <strong>%s</strong> Email subscription New post in the blog “%s” Subscribed via email WARNING: Email subscription works only if Cron scheduled task for the Blog app is properly configured (%s), and it appears that Cron was not setup for your Webasyst installation yet. Please contact your server administrator, or follow instructions given in the Plugins > Email subscription page of the Blog app. Click OK to subscribe anyway (subscription will be enabled, but will start to work only when Cron is enabled). none Project-Id-Version: blog/plugins/emailsubscription
POT-Creation-Date: 2012-05-28 13:21+0400
PO-Revision-Date: 
Last-Translator: 
Language-Team: blog/plugins/emailsubscription
MIME-Version: 1.0
Content-Type: text/plain; charset=utf-8
Content-Transfer-Encoding: 8bit
Plural-Forms: nplurals=3; plural=((((n%10)==1)&&((n%100)!=11))?(0):(((((n%10)>=2)&&((n%10)<=4))&&(((n%100)<10)||((n%100)>=20)))?(1):2));
X-Poedit-SourceCharset: utf-8
X-Poedit-Basepath: .
X-Generator: Poedit 1.5.5
X-Poedit-SearchPath-0: .
X-Poedit-SearchPath-1: .
 Позволяет пользователям бекенда получать email-уведомления о новых записях в блогах CRON для приложения «Блог» включен и работает. Время последнего запуска: <strong>%s</strong> Email-уведомления Для работы плагина необходимо настроить CRON для выполнения действий приложения «Блог» по расписанию. Инструкция для CRON (<a href="http://www.webasyst.ru/developers/docs/tips/cron/" target="_blank">документация по настройке</a>):<br /><br /> <strong>%s</strong> Подписка по почте Новый пост в блоге «%s» Подписаны на этот блог по электронной почте ВНИМАНИЕ: Подписка на уведомления о новых записях в блоге будет работать только если для приложения «Блог» настроен и работает CRON (%s). Для данной установки «Блога» CRON не подключен. Пожалуйста, обратитесь к системному администратору или следуйте инструкциям по настройке CRON, приведенным на странице «Плагины > Подписка по почте» приложения «Блог». Щелкните «ОК», чтобы включить подписку (подписка будет включена, однако начнет работать только после того, как CRON будет настроен). нет 
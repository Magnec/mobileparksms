<?php

namespace Drupal\mobilpark_sms_gateway\EventSubscriber;

use Drupal\Core\Database\Database;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

class UserStatusCheckSubscriber implements EventSubscriberInterface {
  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * RequestSubscriber constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\lightning_api\OAuthKey $key
   *   The OAuth keys service.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    //\Drupal::messenger()->addMessage('memnunum');
    return [
      KernelEvents::REQUEST => 'onRequest',
    ];
  }

  public function onRequest() {
    
    \Drupal::cache()->deleteAll();
    $user_id = \Drupal::currentUser()->id();
    $user = User::load($user_id);

    $phone_number = $user->phone_number->value;
    
    if($phone_number) {
        $connection = Database::getConnection();
        $query = $connection->select('sms_phone_number_verification', 's')
            ->fields('s', ['status'])
            ->condition('phone', $phone_number, '=')
            ->orderBy('created', 'DESC') // 'created' alanını kullanarak en son eklenen kodu al
            ->range(0, 1) // Yalnızca ilk satırı al
            ->execute()
            ->fetchAssoc();
        if($query)  {
            $status = $query['status'];

            if($status == '0') {
                $currentRoute = $this->routeMatch->getRouteName();

                if($currentRoute != 'mobilpark_sms_gateway.verify_otp_form' && $currentRoute != 'user.logout.confirm') {
                    $response = new RedirectResponse('/tr/verify-otp');
                    $response->send();
                }
            }
        }
    }
  }
}
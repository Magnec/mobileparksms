<?php

namespace Drupal\mobilpark_sms_gateway\Plugin\SmsGateway;

use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\sms\Message\SmsMessageInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageResultStatus;

/**
 * Provides MobilPark SMS gateway.
 *
 * @SmsGateway(
 *   id = "mobilpark_gateway",
 *   label = @Translation("MobilPark SMS Gateway"),
 *   outgoing_message_max_recipients = -1,
 * )
 */
class MobilParkGateway extends SmsGatewayPluginBase {


  function mymodule_custom_function() {
    \Drupal::logger('custom')->notice('My custom function was executed!');
  }
  
  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms_message) {
    \Drupal::messenger()->addMessage('Sms gönderildi');
    \Drupal::logger('mobilpark_sms_gateway')->notice('MobilPark send() metodu çalıştırıldı.');

    // MobilPark API endpoint ve API key.
    $api_url = 'http://otpservice.mobilpark.biz/http/SendMsg.aspx';
    $username = $this->configuration['username']; // Yapılandırmadan API key alınır.
    $password = $this->configuration['password'];
    $from = $this->configuration['from']; // Yapılandırmadan from ID alınır.

    // SMS bilgilerini al.
    $recipients = $sms_message->getRecipients();
    $message = $sms_message->getMessage();

    \Drupal::logger('mobilpark_sms_gateway')->notice(json_encode($recipients));
    // MobilPark API'sine gönderilecek veri.
    $data = [
      'username' => $username,
      'password' => $password,
      'to' => implode(',', $recipients),
      'messageType'=> 'sms',
      'text' => $message,
      'from' => $from,
    ];

    // HTTP isteği gönder.
    $client = new Client();
    $result = new SmsMessageResult();
    try {
        $response = $client->post($api_url, [
            'form_params' => $data,
        ]);
    
        \Drupal::logger('mobilpark_sms_gateway')->notice('SMS API yanıtı: ' . $response->getBody()->getContents());
    
        return $result;
    } catch (RequestException $e) {
        \Drupal::logger('mobilpark_sms_gateway')->error('SMS gönderimi başarısız: @error', ['@error' => $e->getMessage()]);
    }
    //try {
  /*     $response = $client->post($api_url, [
        'json' => $data,
      ]);

      // Yanıtı işle.
      if ($response->getStatusCode() == 200) {
        $response_data = json_decode($response->getBody(), TRUE);
        if ($response_data['status'] == 'success') {
          return TRUE; // SMS başarıyla gönderildi.
        }
      } */
   // } catch (RequestException $e) {
    //  \Drupal::logger('mobilpark_sms_gateway')->error('SMS gönderimi başarısız: @error', ['@error' => $e->getMessage()]);
    //}

    $result
    ->setError(SmsMessageResultStatus::ERROR)
    ->setErrorMessage((string) $this->t('SMS gönderimi başarısız oldu.'));

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'username' => '',
      'password' => '',
      'from' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $this->configuration['username'],
      '#required' => TRUE,
    ];
    $form['password'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Password'),
        '#default_value' => $this->configuration['password'],
        '#required' => TRUE,
      ];
    $form['from'] = [
      '#type' => 'textfield',
      '#title' => $this->t('from'),
      '#default_value' => $this->configuration['from'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['username'] = $form_state->getValue('username');
    $this->configuration['password'] = $form_state->getValue('password');
    $this->configuration['from'] = $form_state->getValue('from');
  }
}
<?php

namespace Drupal\mobilpark_sms_gateway\Form;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Provides an OTP verification form.
 */
class VerifyOTPForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mobilpark_sms_gateway_verify_otp_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $form['otp_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Doğrulama Kodu'),
      '#required' => FALSE,
    ];

    $form['verify_otp'] = [
      '#type' => 'submit',
      '#value' => $this->t('Doğrula'),
      "#id" => "verify_otp"
    ];
    
    $form['send_otp'] = [
      '#type' => 'submit',
      '#value' => $this->t('Tekrar Gönder'),
      "#id" => "send_otp",
      '#attributes' => [
        'class' => ['d-none'],
      ],
    ];

    // JS dosyasını çağırıyoruz
    $form['#attached']['library'][] = 'mobilpark_sms_gateway/sms_verification';

    return $form;
}



 /**
 * {@inheritdoc}
 */
public function submitForm(array &$form, FormStateInterface $form_state) {
  \Drupal::cache()->deleteAll();

  if ($form_state->getTriggeringElement()['#id'] == 'verify_otp') {
    $user_id = \Drupal::currentUser()->id();
    $user = User::load($user_id);
  
    if (!$user) {
      $this->messenger()->addError($this->t('Kullanıcı bulunamadı.'));
      return;
    }
  
    // Alanları doğru isimle al
    $phone_number = $user->phone_number->value;
      //dump($phone_number);
   $status = $user->status->value;
   //dump($status);
     // Veritabanından ilgili telefon numarasına ait OTP kodunu al
     $connection = Database::getConnection();
     $query = $connection->select('sms_phone_number_verification', 's')
    ->fields('s', ['code'])
    ->condition('phone', $phone_number, '=')
    ->orderBy('created', 'DESC') // 'created' alanını kullanarak en son eklenen kodu al
    ->range(0, 1) // Yalnızca ilk satırı al
    ->execute()
    ->fetchAssoc();
   
     if (!$query || empty($query['code'])) {
       $this->messenger()->addError($this->t('Doğrulama kodu bulunamadı.'));
       return;
     }
   
     $stored_otp = trim($query['code']);
     $entered_otp = trim($form_state->getValue('otp_code'));
   
     if ($entered_otp === $stored_otp) {
       // Doğrulama başarılı → Status alanını 1 yap
       $update = $connection->update('sms_phone_number_verification')
       ->fields(['status' => 1]) // Status güncelleniyor
       ->condition('phone', $phone_number, '=')
       ->condition('code', $stored_otp, '=')
       ->execute();
       
       /*
       $connection->insert('sms_phone_number')
         ->fields([
          "bundle" => "user",
          "entity_id" => $user_id,
          "revision_id" => $user_id,
          "lang_code" => "tr",
          "phone_number_value" => $phone_number,
         ]) 
         ->execute();
         */
   
       if ($update) {
         \Drupal::logger('sms_verification')->notice('Telefon numarası doğrulandı: @phone', ['@phone' => $phone_number]);
         $this->messenger()->addStatus($this->t('Telefon numaranız başarıyla doğrulandı.'));
       } else {
         \Drupal::logger('sms_verification')->error('Status güncellenemedi: @phone', ['@phone' => $phone_number]);
         $this->messenger()->addError($this->t('Bir hata oluştu, lütfen tekrar deneyin.'));
         return;
       }
      // Kullanıcıyı yönlendir
      $form_state->setRedirect('<front>'); // Ana sayfaya yönlendirme
    } else {
      $this->messenger()->addError($this->t('Girilen doğrulama kodu yanlış.'));
    }

    return;
  }

    $user_id = \Drupal::currentUser()->id();
    $user = User::load($user_id);
  
    if (!$user) {
      $this->messenger()->addError($this->t('Kullanıcı bulunamadı.'));
      return;
    }
  
    // Alanları doğru isimle al
    $phone_number = $user->phone_number->value;
    //dump($user->phone_number);
    try {
  
      $phone_number_verification_provider = \Drupal::service("sms.phone_number.verification");
      $a = $phone_number_verification_provider->newPhoneVerification($user, $phone_number);
  
     
  
    } catch (\Exception $e) {
 
    }
}

}

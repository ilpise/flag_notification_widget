<?php

namespace Drupal\flag_notifications_widget\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
// use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

use  Drupal\flag\FlagService;

/**
 * Provides a block with list of notification items.
 *
 * @Block(
 *   id = "flag_notifications_widget_block",
 *   admin_label = @Translation("Flag Notifications widget block"),
 *   category = @Translation("Notifications widget")
 * )
 */
class FlagNotificationsWidgetBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * Drupal\Core\Session\AccountInterface definition.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The Database Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The Database Connection.
   *
   * @var \Drupal\flag\FlagService
   */
  protected $flag;

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('database'),
      $container->get('flag')
    );
  }

  /**
     * @param array $configuration
     * @param string $plugin_id
     * @param mixed $plugin_definition
     * @param \Drupal\Core\Session\AccountProxyInterface $current_user
     * @param \Drupal\Core\Database\Connection $database
     * @param \Drupal\flag\FlagService $flag
     */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, Connection $database, $flag) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->database    = $database;
    $this->flag        = $flag;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Load all the flags
    // Remeber the kind of flag can be filtered in the block configuration
    $flags = $this->flag->getAllFlags();

    $options = [];
    foreach ($flags as $flag) {
      // Get the flag_id from the Flag.
      $flag_id = $flag->id();
      $flag_label = $flag->label();
      $options[$flag_id] = $flag_label;
    }

    // kint($options);
    // Retrieve existing configuration for this block.
    $config = $this->getConfiguration();
    // print_r($config['block_flag_name']);

    // Add a form field to the existing block configuration form.
    $form['block_flag_name'] = [
      '#type'    => 'select',
      '#title'   => $this->t('Flag name'),
      '#description' => $this->t('Flagged entities to show in the block by flag name.'),
      '#options' => $options,
      '#default_value' => isset($config['block_flag_name']) ? $config['block_flag_name'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['block_flag_name'] = $values['block_flag_name'];

    // $this->setConfigurationValue('block_flag_name', $form_state->getValue('block_flag_name'));
    // $this->setConfigurationValue('block_notification_logs_display', $form_state->getValue('block_notification_logs_display'));
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIf($account->isAuthenticated());
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $connection = $this->database;
    $config     = $this->getConfiguration();

    // Get logged user session.
    $currentUser = $this->currentUser;

    $uid = $currentUser->id();
    $notificationType = 0;
    $totalCount = 0;
    $unreadCount = 0;
    $notificationList = [];


    $query = $connection->select('node_field_data', 'node_field_data');
    /**
    Use this query for personal Flags
    */
    $query->innerJoin('flagging', 'flagging_node_field_data', "(node_field_data.nid = flagging_node_field_data.entity_id) AND (flagging_node_field_data.flag_id = '".$config['block_flag_name']."' AND flagging_node_field_data.uid = ".$uid.")");
    /**
    Use this query for global Flags
    */
    // $query->innerJoin('flagging', 'flagging_node_field_data', "(node_field_data.nid = flagging_node_field_data.entity_id) AND (flagging_node_field_data.flag_id = '".$config['block_flag_name']."')");
    /**
    Postgres need CAST
    */
    // $query->innerJoin('flagging', 'flagging_node_field_data', "CAST(node_field_data.nid as TEXT) = CAST(flagging_node_field_data.entity_id as TEXT) AND (flagging_node_field_data.flag_id = '".$config['block_flag_name']."' AND flagging_node_field_data.uid = ".$uid.")");
    // $query->innerJoin('users_field_data', 'users_field_data_node_field_data', 'CAST(node_field_data.uid as TEXT) = CAST(users_field_data_node_field_data.uid as TEXT)' );
    $query->addfield('node_field_data', 'nid', 'nid');
    $query->addfield('node_field_data', 'type', 'type');
    $query->addfield('node_field_data', 'title', 'title');
    $query->addfield('node_field_data', 'uid', 'users_field_data_node_field_data_uid');
    $query->addfield('flagging_node_field_data', 'id', 'flagging_node_field_data_id');
    // $query->addfield('users_field_data_node_field_data', 'uid', 'users_field_data_node_field_data_uid');
    $query->condition('node_field_data.status', 1, '=');

    // print_r($query->__toString());
    // print_r($query->arguments());

    $response = $query->execute();

    while ($notifications = $response->fetchObject()) {
      // print_r($notifications);
      // \Drupal::service('messenger')->addMessage(print_r($notifications, TRUE));
      // stdClass Object ( [nid] => 24 // node id della water_offer/water_request
      //                   [flagging_node_field_data_id] => 4 // flag_id
      //                   [users_field_data_node_field_data_uid] => 5 // user id -> l' innerJoin commentato può servire per estrarre lo username dalla tabella users_field_data
      // )
      if (!empty($notifications->nid)) {
        $notificationList[] = [
          'id'      => $notifications->nid, // node id della water_offer/water_request
          'type'    => $notifications->type, // node type water_offer or water_request
          // 'title'    => $notifications->title, // node title della water_offer or water_request
          'flag_id' => $notifications->flagging_node_field_data_id,
          'message' => $notifications->title,
          'status'  => $notifications->users_field_data_node_field_data_uid,
        ];

        $totalCount++;
      }
    }

    return [
      '#theme' => 'flag_notifications_widget',
      '#attached' => array(
        'library' => array('flag_notifications_widget/flag_notifications_widget'),
      ),
      '#uid' => $uid,
      '#notification_type' => $notificationType,
      '#total' => $totalCount,
      '#unread' => $totalCount,
      '#notification_list' => $notificationList,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}

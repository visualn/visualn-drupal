<?php

namespace Drupal\visualn_data_sources\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\visualn_data_sources\Entity\VisualNDataSetInterface;

/**
 * Class VisualNDataSetController.
 *
 *  Returns responses for VisualN Data Set routes.
 */
class VisualNDataSetController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a VisualN Data Set  revision.
   *
   * @param int $visualn_data_set_revision
   *   The VisualN Data Set  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($visualn_data_set_revision) {
    $visualn_data_set = $this->entityManager()->getStorage('visualn_data_set')->loadRevision($visualn_data_set_revision);
    $view_builder = $this->entityManager()->getViewBuilder('visualn_data_set');

    return $view_builder->view($visualn_data_set);
  }

  /**
   * Page title callback for a VisualN Data Set  revision.
   *
   * @param int $visualn_data_set_revision
   *   The VisualN Data Set  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($visualn_data_set_revision) {
    $visualn_data_set = $this->entityManager()->getStorage('visualn_data_set')->loadRevision($visualn_data_set_revision);
    return $this->t('Revision of %title from %date', ['%title' => $visualn_data_set->label(), '%date' => format_date($visualn_data_set->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a VisualN Data Set .
   *
   * @param \Drupal\visualn_data_sources\Entity\VisualNDataSetInterface $visualn_data_set
   *   A VisualN Data Set  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(VisualNDataSetInterface $visualn_data_set) {
    $account = $this->currentUser();
    $langcode = $visualn_data_set->language()->getId();
    $langname = $visualn_data_set->language()->getName();
    $languages = $visualn_data_set->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $visualn_data_set_storage = $this->entityManager()->getStorage('visualn_data_set');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $visualn_data_set->label()]) : $this->t('Revisions for %title', ['%title' => $visualn_data_set->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all visualn data set revisions") || $account->hasPermission('administer visualn data set entities')));
    $delete_permission = (($account->hasPermission("delete all visualn data set revisions") || $account->hasPermission('administer visualn data set entities')));

    $rows = [];

    $vids = $visualn_data_set_storage->revisionIds($visualn_data_set);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\visualn_data_sources\VisualNDataSetInterface $revision */
      $revision = $visualn_data_set_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $visualn_data_set->getRevisionId()) {
          $link = $this->l($date, new Url('entity.visualn_data_set.revision', ['visualn_data_set' => $visualn_data_set->id(), 'visualn_data_set_revision' => $vid]));
        }
        else {
          $link = $visualn_data_set->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.visualn_data_set.translation_revert', ['visualn_data_set' => $visualn_data_set->id(), 'visualn_data_set_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.visualn_data_set.revision_revert', ['visualn_data_set' => $visualn_data_set->id(), 'visualn_data_set_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.visualn_data_set.revision_delete', ['visualn_data_set' => $visualn_data_set->id(), 'visualn_data_set_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['visualn_data_set_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}

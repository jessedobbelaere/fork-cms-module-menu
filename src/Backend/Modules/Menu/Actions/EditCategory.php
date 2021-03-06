<?php

namespace Backend\Modules\Menu\Actions;

use Backend\Core\Engine\Base\ActionEdit;
use Backend\Core\Engine\Form;
use Backend\Core\Engine\Language;
use Backend\Core\Engine\Meta;
use Backend\Core\Engine\Model;
use Backend\Modules\Menu\Engine\Model as BackendMenuModel;

/**
 * This is the edit category action, it will display a form to edit an existing category.
 *
 * @author Jesse Dobbelaere <jesse@dobbelaere-ae.be>
 */
class EditCategory extends ActionEdit
{
    /**
     * Execute the action
     */
    public function execute()
    {
        parent::execute();

        $this->getData();
        $this->loadForm();
        $this->validateForm();

        $this->parse();
        $this->display();
    }

    /**
     * Get the data
     */
    private function getData()
    {
        $this->id = $this->getParameter('id', 'int');
        if ($this->id == null || !BackendMenuModel::existsCategory($this->id)) {
            $this->redirect(
                Model::createURLForAction('categories') . '&error=non-existing'
            );
        }

        $this->record = BackendMenuModel::getCategory($this->id);
    }

    /**
     * Load the form
     */
    private function loadForm()
    {
        // create form
        $this->frm = new Form('editCategory');
        $this->frm->addText('title', $this->record['title']);

        $this->meta = new Meta($this->frm, $this->record['meta_id'], 'title', true);
        $this->meta->setURLCallback('Backend\Modules\Menu\Engine\Model', 'getURLForCategory', array($this->record['id']));
    }

    /**
     * Parse the form
     */
    protected function parse()
    {
        parent::parse();

        // assign the data
        $this->tpl->assign('item', $this->record);

        if (BackendMenuModel::getItemsInCategory($this->record['id']) == 0) {
            $this->tpl->assign('emptyCategory', true);
        }
    }

    /**
     * Validate the form
     */
    private function validateForm()
    {
        if ($this->frm->isSubmitted()) {
            $this->frm->cleanupFields();

            // validate fields
            $this->frm->getField('title')->isFilled(Language::err('TitleIsRequired'));
            $this->meta->validate();

            if ($this->frm->isCorrect()) {
                // build item
                $item['id'] = $this->id;
                $item['language'] = $this->record['language'];
                $item['title'] = $this->frm->getField('title')->getValue();
                $item['meta_id'] = $this->meta->save(true);

                // update the item
                BackendMenuModel::updateCategory($item);
                Model::triggerEvent($this->getModule(), 'after_edit_category', array('item' => $item));

                // everything is saved, so redirect to the overview
                $this->redirect(
                    Model::createURLForAction('categories') . '&report=edited-category&var=' .
                    urlencode($item['title']) . '&highlight=row-' . $item['id']
                );
            }
        }
    }
}

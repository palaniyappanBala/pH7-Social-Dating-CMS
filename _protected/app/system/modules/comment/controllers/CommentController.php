<?php
/**
 * @author         Pierre-Henry Soria <ph7software@gmail.com>
 * @copyright      (c) 2012-2017, Pierre-Henry Soria. All Rights Reserved.
 * @license        GNU General Public License; See PH7.LICENSE.txt and PH7.COPYRIGHT.txt in the root directory.
 * @package        PH7 / App / System / Module / Comment / Controller
 */
namespace PH7;

use
PH7\Framework\Security\Ban\Ban,
PH7\Framework\Http\Http,
PH7\Framework\Navigation\Page,
PH7\Framework\Mvc\Router\Uri;

class CommentController extends Controller
{

    private $oComment, $oCommentModel, $sTable, $sTitle, $sMsg, $iId;

    public function __construct()
    {
        parent::__construct();
        $this->oCommentModel = new CommentModel();

        $this->sTable = $this->httpRequest->get('table');
        $this->view->table = $this->sTable;
        $this->iId = (is_numeric($this->httpRequest->get('id'))) ? $this->httpRequest->get('id') : null;

        // Predefined meta_keywords tags
        $this->view->meta_keywords = t('comment,comments,social,community,friend,social network,people,dating,post,wall,social dating');

        // Adding Css Style for the Comment Post
        $this->design->addCss(PH7_LAYOUT . PH7_SYS . PH7_MOD . $this->registry->module . PH7_SH . PH7_TPL . PH7_TPL_MOD_NAME . PH7_SH . PH7_CSS, 'common.css');
    }

    public function index()
    {
        Framework\Url\Header::redirect(Uri::get('error', 'http', 'index'));
    }

    public function read()
    {
        // Adding JavaScript file for Ajax Comment
        $this->design->addJs(PH7_LAYOUT . PH7_SYS . PH7_MOD . $this->registry->module . PH7_SH . PH7_TPL . PH7_TPL_MOD_NAME . PH7_SH . PH7_JS, 'comment.js');

        $this->sTitle = t('Read Comment');
        $this->view->page_title = $this->sTitle;
        $this->view->meta_description = $this->sTitle;
        $this->view->h1_title = $this->sTitle;

        $this->view->h4_title = CommentCore::count($this->iId, $this->sTable);

        $oPage = new Page;
        $this->view->total_pages = $oPage->getTotalPages($this->oCommentModel->total($this->iId, $this->sTable), 15);
        $this->view->current_page = $oPage->getCurrentPage();
        $oComment = $this->oCommentModel->read($this->iId, 1, $oPage->getFirstItem(), $oPage->getNbItemsByPage(), $this->sTable);
        unset($oPage);

        if (!empty($oComment))
        {
            $this->view->avatarDesign = new AvatarDesignCore(); // Avatar Design Class
            $this->view->member_id = $this->session->get('member_id');
            $this->view->csrf_token = (new Framework\Security\CSRF\Token)->generate('comment');

            $this->view->comment = $oComment;
        }
        else
        {
            $this->_notFound();
        }
        $this->output();
    }

    public function post()
    {
        $oComment = $this->oCommentModel->get($this->iId, 1, $this->sTable);

        if (!empty($oComment))
        {
            $this->sTitle = t("Read the <span class='pH1'>%0%</span>'s comment", $oComment->firstName);
            $this->view->page_title = $this->sTitle;
            $this->view->meta_description = t('Read comment of %0%, %1%. %2%', $oComment->firstName, $oComment->username, substr(Ban::filterWord($oComment->comment, false), 0, 150));
            $this->view->meta_keywords = t('comment,%0%', str_replace(' ', ',', substr(Ban::filterWord($oComment->comment, false), 0, 250)));
            $this->view->h1_title = $this->sTitle;

            $this->view->avatarDesign = new AvatarDesignCore(); // Avatar Design Class
            $this->view->member_id = $this->session->get('member_id');

            $this->view->com = $oComment;
        }
        else
        {
            $this->_notFound();
            // Modified the message error
            $this->view->error = t('No comments yet, please return to the <a href="%0%">previous page</a>.', 'javascript:history.back();');
        }
        $this->output();
    }

    public function add()
    {
        $this->view->page_title = t('Add a new comment');
        $this->output();
    }

    public function edit()
    {
        $this->view->page_title = t('Edit the comment');
        $this->output();
    }

    public function delete()
    {
        if ((($this->session->get('member_id') == $this->httpRequest->post('recipient_id')) || ($this->session->get('member_id') == $this->httpRequest->post('sender_id'))) || AdminCore::auth())
        {
            $this->sTable = $this->httpRequest->post('table');

            if ($this->oCommentModel->delete($this->httpRequest->post('id'), $this->httpRequest->post('recipient_id'), $this->httpRequest->post('sender_id'), $this->sTable))
            {
                /* Clean All Data of CommentModel Cache */
                (new Framework\Cache\Cache)->start(CommentCoreModel::CACHE_GROUP, null, null)->clear();

                $this->sMsg = t('The comment has been deleted!');
            }
            else
            {
                $this->sMsg = t('Your comment does not exist anymore.');
            }
        }
        else
        {
            $this->sMsg = t('Whoops! The comment could not be removed!');
        }

        Framework\Url\Header::redirect(Uri::get('comment', 'comment', 'read', $this->sTable . ',' . $this->httpRequest->post('recipient_id')), $this->sMsg);
    }

    /**
     * Set a Not Found Error Message with HTTP 404 Code Status.
     *
     * @return void
     */
    private function _notFound()
    {
        Http::setHeadersByCode(404);
        $this->view->page_title = t('Comment Not Found');
        $this->view->error = t('No comments yet, <a class="bold" href="%0%">add one</a>!', Uri::get('comment', 'comment', 'add', $this->sTable . ',' . $this->str->escape($this->httpRequest->get('id'))));
    }

    public function __destruct()
    {
        unset($this->oComment, $this->oCommentModel, $this->sTable, $this->sTitle, $this->sMsg, $this->iId);
    }

}

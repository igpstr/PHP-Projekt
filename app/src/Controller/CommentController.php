<?php
/**
 * Comment controller.
 */

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Task;
use App\Form\Type\CommentType;
use App\Service\CommentServiceInterface;
use App\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class CommentController.
 */
#[Route('/comment')]
class CommentController extends AbstractController
{
    /**
     * Comment service.
     */
    private CommentServiceInterface $commentService;

    /**
     * Translator.
     */
    private TranslatorInterface $translator;

    /**
     * Constructor.
     *
     * @param CommentServiceInterface $commentService Comment service
     * @param TranslatorInterface     $translator     Translator
     */
    public function __construct(CommentServiceInterface $commentService, TranslatorInterface $translator)
    {
        $this->commentService = $commentService;
        $this->translator = $translator;
    }

    /**
     * Index action.
     *
     * @param Request $request HTTP Request
     *
     * @return Response HTTP response
     */
    #[Route(name: 'comment_index', methods: 'GET')]
    public function index(Request $request): Response
    {
        $pagination = $this->commentService->getPaginatedList(
            $request->query->getInt('page', 1)
        );

        return $this->render('comment/index.html.twig', ['pagination' => $pagination]);
    }

    /**
     * Show action.
     *
     * @param Comment $comment Comment entity
     *
     * @return Response HTTP response
     */
    #[Route('/{id}', name: 'comment_show', requirements: ['id' => '[1-9]\d*'], methods: 'GET')]
    public function show(Comment $comment): Response
    {
        return $this->render('comment/show.html.twig', ['comment' => $comment]);
    }

    /**
     * Create action.
     *
     * @param Request $request HTTP request
     * @param Task    $task
     *
     * @return Response HTTP response
     */
    #[Route('/create/{id}', name: 'comment_create', methods: 'GET|POST')]
    public function create(Request $request, Task $task): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $comment = new Comment();
        $comment->setAuthor($user);
        $comment->setTask($task);
        $form = $this->createForm(
            CommentType::class,
            $comment,
            ['action' => $this->generateUrl('comment_create', ['id' => $task->getId()])]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commentService->save($comment);

            $this->addFlash(
                'success',
                $this->translator->trans('message.created_successfully')
            );

            return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
        }

        return $this->render(
            'comment/create.html.twig',
            ['form' => $form->createView()]
        );
    }


    /**
     * Edit action.
     *
     * @param Request $request
     * @param Comment $comment
     * @param Task    $task
     *
     * @return Response
     */
    #[Route('/{id}/edit', name: 'comment_edit', requirements: ['id' => '[1-9]\d*'], methods: 'GET|PUT')]
    #[IsGranted('EDIT', subject: 'comment')]
    public function edit(Request $request, Comment $comment, Task $task): Response
    {
        $form = $this->createForm(
            CommentType::class,
            $comment,
            [
                'method' => 'PUT',
                'action' => $this->generateUrl('comment_edit', ['id' => $comment->getId()]),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commentService->save($comment);

            $this->addFlash(
                'success',
                $this->translator->trans('message.edited_successfully')
            );

            return $this->redirectToRoute('task_show', ['id' => $comment->getTask()->getId()]);
        }

        return $this->render(
            'comment/edit.html.twig',
            [
                'form' => $form->createView(),
                'comment' => $comment,
            ]
        );
    }

    /**
     * Delete action.
     *
     * @param Request $request HTTP request
     * @param Comment $comment Comment entity
     * @param Task    $task
     *
     * @return Response HTTP response
     */
    #[Route('/{id}/delete', name: 'comment_delete', requirements: ['id' => '[1-9]\d*'], methods: 'GET|DELETE')]
    public function delete(Request $request, Comment $comment): Response
    {
        $form = $this->createForm(
            FormType::class,
            $comment,
            [
                'method' => 'DELETE',
                'action' => $this->generateUrl('comment_delete', ['id' => $comment->getId()]),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commentService->delete($comment);

            $this->addFlash(
                'success',
                $this->translator->trans('message.deleted_successfully')
            );

            return $this->redirectToRoute('task_show', ['id' => $comment->getTask()->getId()]);
        }

        return $this->render(
            'comment/delete.html.twig',
            [
                'form' => $form->createView(),
                'comment' => $comment,
            ]
        );
    }
}

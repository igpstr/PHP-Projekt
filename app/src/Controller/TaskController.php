<?php
/**
 * Task controller.
 */

namespace App\Controller;

use App\Entity\Tag;
use App\Entity\Task;
use App\Form\Type\TaskType;
use App\Repository\TaskRepository;
use App\Service\CommentService;
use App\Service\TaskServiceInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Class TaskController.
 */
#[Route('/task')]
class TaskController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    /**
     * Task service.
     */
    private TaskServiceInterface $taskService;

    /**
     * Translator.
     */
    private TranslatorInterface $translator;

    /**
     * Slugger.
     */
    private SluggerInterface $slugger;

    /**
     * Constructor.
     *
     * @param TaskServiceInterface   $taskService   Task service
     * @param TranslatorInterface    $translator    Translator
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(TaskServiceInterface $taskService, TranslatorInterface $translator, EntityManagerInterface $entityManager, SluggerInterface $slugger)
    {
        $this->taskService = $taskService;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->slugger = $slugger;
    }

    /**
     * Index action.
     *
     * @param Request $request HTTP Request
     *
     * @return Response HTTP response
     */
    #[Route(name: 'task_index', methods: 'GET')]
    public function index(Request $request): Response
    {
        $pagination = $this->taskService->getPaginatedList(
            $request->query->getInt('page', 1)
        );

        return $this->render('task/index.html.twig', ['pagination' => $pagination]);
    }

    /**
     * Show action.
     *
     * @param Request        $request
     * @param CommentService $commentService
     * @param Task           $task
     *
     * @return Response
     */
    #[Route('/{id}', name: 'task_show', requirements: ['id' => '[1-9]\d*'], methods: 'GET')]
    public function show(Request $request, CommentService $commentService, Task $task): Response
    {
        $pagination = $commentService->getPaginatedListByTask($request->query->getInt('page', 1), $task);

        return $this->render(
            'task/show.html.twig',
            ['task' => $task, 'pagination' => $pagination]
        );
    }

    /**
     * Create action.
     *
     * @param Request $request HTTP request
     *
     * @return Response HTTP response
     */
    #[Route('/create', name: 'task_create', methods: 'GET|POST')]
    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $task = new Task();
        $task->setAuthor($user);
        $form = $this->createForm(
            TaskType::class,
            $task,
            ['action' => $this->generateUrl('task_create')]
        );
        $form->handleRequest($request);

//        if ($form->isSubmitted() && $form->isValid()) {
        if ($form->isSubmitted()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                // Get the original filename
                $originalFilename = $imageFile->getClientOriginalName();
                // Sanitize the filename
                $safeFilename = $this->slugger->slug($originalFilename);

                try {
                    // Move the file to the directory where images are stored using the original filename
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $originalFilename
                    );
                } catch (FileException $e) {
                    // Handle exception if something happens during file upload
                }

                // Update the 'image' property to store the original image file name
                $task->setImage($originalFilename);
            }

            $tags = $form->get('tags')->getData();
            // Remove all existing tags from the task
            foreach ($task->getTags() as $tag) {
                $task->removeTag($tag);
            }

// Add new or existing tags to the task
            foreach ($tags as $tag) {
                // Check if the tag already exists
                $existingTag = $this->entityManager->getRepository(Tag::class)->findOneBy(['title' => $tag->getTitle()]);
                if ($existingTag) {
                    $task->addTag($existingTag);
                } else {
                    // If the tag doesn't exist, add it to the task
                    $task->addTag($tag);
                }
            }

            $this->taskService->save($task);

            $this->addFlash(
                'success',
                $this->translator->trans('message.created_successfully')
            );

            return $this->redirectToRoute('task_index');
        }

        return $this->render(
            'task/create.html.twig',
            ['form' => $form->createView()]
        );
    }

    /**
     * Edit action.
     *
     * @param Request $request HTTP request
     * @param Task    $task    Task entity
     *
     * @return Response HTTP response
     */
    #[Route('/{id}/edit', name: 'task_edit', requirements: ['id' => '[1-9]\d*'], methods: 'GET|PUT')]
    public function edit(Request $request, Task $task): Response
    {
        $form = $this->createForm(
            TaskType::class,
            $task,
            [
                'method' => 'PUT',
                'action' => $this->generateUrl('task_edit', ['id' => $task->getId()]),
            ]
        );
        $form->handleRequest($request);

//        if ($form->isSubmitted() && $form->isValid()) {
        if ($form->isSubmitted()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                // Get the original filename
                $originalFilename = $imageFile->getClientOriginalName();
                // Sanitize the filename
                $safeFilename = $this->slugger->slug($originalFilename);

                try {
                    // Move the file to the directory where images are stored using the original filename
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $originalFilename
                    );
                } catch (FileException $e) {
                    // Handle exception if something happens during file upload
                }

//                $task->setImage(null);

                // If there's an old image, remove it
//                $oldImage = $task->getImage();
//                if ($oldImage) {
//                    $oldImagePath = $this->getParameter('images_directory').'/'.$oldImage;
//                    if (file_exists($oldImagePath)) {
//                        unlink($oldImagePath);
//                    }
//                }

                // Update the 'image' property to store the original image file name
                $task->setImage($originalFilename);
            }

            $tags = $form->get('tags')->getData();
            // Remove all existing tags from the task
            foreach ($task->getTags() as $tag) {
                $task->removeTag($tag);
            }

// Add new or existing tags to the task
            foreach ($tags as $tag) {
                // Check if the tag already exists
                $existingTag = $this->entityManager->getRepository(Tag::class)->findOneBy(['title' => $tag->getTitle()]);
                if ($existingTag) {
                    $task->addTag($existingTag);
                } else {
                    // If the tag doesn't exist, add it to the task
                    $task->addTag($tag);
                }
            }

            $this->taskService->save($task);

            $this->addFlash(
                'success',
                $this->translator->trans('message.edited_successfully')
            );

            return $this->redirectToRoute('task_index');
        }

        return $this->render(
            'task/edit.html.twig',
            [
                'form' => $form->createView(),
                'task' => $task,
            ]
        );
    }

    /**
     * Delete action.
     *
     * @param Request $request HTTP request
     * @param Task    $task    Task entity
     *
     * @return Response HTTP response
     */
    #[Route('/{id}/delete', name: 'task_delete', requirements: ['id' => '[1-9]\d*'], methods: 'GET|DELETE')]
    public function delete(Request $request, Task $task): Response
    {
        $form = $this->createForm(
            FormType::class,
            null,
            [
                'method' => 'DELETE',
                'action' => $this->generateUrl('task_delete', ['id' => $task->getId()]),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Log the deletion attempt
            error_log('Attempting to delete task with ID: ' . $task->getId());

            // Delete the associated comments before deleting the task
            $comments = $task->getComments();
            foreach ($comments as $comment) {
                $this->entityManager->remove($comment);
            }

//            // Check if the task has an image and delete the image file
//            if ($task->getImage()) {
//                $imagePath = $this->getParameter('images_directory') . '/' . $task->getImage();
//                // Log the image deletion attempt
//                error_log('Attempting to delete image: ' . $imagePath);
//
//                if (file_exists($imagePath)) {
//                    if (unlink($imagePath)) {
//                        // Log successful image deletion
//                        error_log('Image deleted successfully: ' . $imagePath);
//                    } else {
//                        // Log failed image deletion
//                        error_log('Failed to delete image: ' . $imagePath);
//                    }
//                } else {
//                    // Log image file not found
//                    error_log('Image file not found: ' . $imagePath);
//                }
//            }

            // Remove the task entity
            $this->entityManager->remove($task);
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                $this->translator->trans('message.deleted_successfully')
            );

            return $this->redirectToRoute('task_index');
        }

        return $this->render(
            'task/delete.html.twig',
            [
                'form' => $form->createView(),
                'task' => $task,
            ]
        );
    }
}

<?php
/**
 * Tag controller.
 */

namespace App\Controller;

use App\Entity\Tag;
use App\Form\Type\TagType;
use App\Repository\TaskRepository;
use App\Service\TagServiceInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Class TagController.
 */
#[Route('/tag')]
class TagController extends AbstractController
{
    /**
     * Tag service.
     */
    private TagServiceInterface $tagService;

    /**
     * Translator.
     */
    private TranslatorInterface $translator;

    /**
     * Constructor.
     *
     * @param TagServiceInterface $tagService Tag service
     * @param TranslatorInterface $translator Translator
     */
    public function __construct(TagServiceInterface $tagService, TranslatorInterface $translator)
    {
        $this->tagService = $tagService;
        $this->translator = $translator;
    }

    /**
     * Index action.
     *
     * @param Request $request HTTP Request
     *
     * @return Response HTTP response
     */
    #[Route(name: 'tag_index', methods: 'GET')]
    public function index(Request $request): Response
    {
        $pagination = $this->tagService->getPaginatedList(
            $request->query->getInt('page', 1)
        );

        return $this->render('tag/index.html.twig', ['pagination' => $pagination]);
    }

    /**
     * Show action.
     *
     * @param Tag                $tag            Tag
     * @param TaskRepository     $taskRepository Task repository
     * @param PaginatorInterface $paginator      Paginator
     * @param Request            $request        HTTP request
     *
     * @return Response HTTP response
     */
    #[Route('/{id}', name: 'tag_show', requirements: ['id' => '[1-9]\d*'], methods: 'GET')]
    public function show(Tag $tag, TaskRepository $taskRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $tasksQuery = $taskRepository->findTasksByTag($tag);

        $pagination = $paginator->paginate(
            $tasksQuery,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('tag/show.html.twig', [
            'tag' => $tag,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Create action.
     *
     * @param Request $request HTTP request
     *
     * @return Response HTTP response
     */
    #[Route('/create', name: 'tag_create', methods: 'GET|POST')]
    public function create(Request $request): Response
    {
        $tag = new Tag();
        $form = $this->createForm(
            TagType::class,
            $tag,
            ['action' => $this->generateUrl('tag_create')]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                // Get the original filename
                $originalFilename = $imageFile->getClientOriginalName();
                // Sanitize the filename
//                $safeFilename = $this->slugger->slug($originalFilename);

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
                $tag->setImage($originalFilename);
            }

            $this->tagService->save($tag);

            $this->addFlash(
                'success',
                $this->translator->trans('message.created_successfully')
            );

            return $this->redirectToRoute('tag_index');
        }

        return $this->render('tag/create.html.twig', ['form' => $form->createView()]);
    }

    /**
     * Edit action.
     *
     * @param Request $request HTTP request
     * @param Tag     $tag     Tag entity
     *
     * @return Response HTTP response
     */
    #[Route('/{id}/edit', name: 'tag_edit', requirements: ['id' => '[1-9]\d*'], methods: 'GET|PUT')]
    public function edit(Request $request, Tag $tag): Response
    {
        $form = $this->createForm(
            TagType::class,
            $tag,
            [
                'method' => 'PUT',
                'action' => $this->generateUrl('tag_edit', ['id' => $tag->getId()]),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                // Get the original filename
                $originalFilename = $imageFile->getClientOriginalName();
                // Sanitize the filename
//                $safeFilename = $this->slugger->slug($originalFilename);

                try {
                    // Move the file to the directory where images are stored using the original filename
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $originalFilename
                    );
                } catch (FileException $e) {
                    // Handle exception if something happens during file upload
                }

                // If there's an old image, remove it
                $oldImage = $tag->getImage();
                if ($oldImage) {
                    $oldImagePath = $this->getParameter('images_directory').'/'.$oldImage;
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                // Update the 'image' property to store the original image file name
                $tag->setImage($originalFilename);
            }

            $this->tagService->save($tag);

            $this->addFlash(
                'success',
                $this->translator->trans('message.edited_successfully')
            );

            return $this->redirectToRoute('tag_index');
        }

        return $this->render(
            'tag/edit.html.twig',
            [
                'form' => $form->createView(),
                'tag' => $tag,
            ]
        );
    }

    /**
     * Delete action.
     *
     * @param Request $request HTTP request
     * @param Tag     $tag     Tag entity
     *
     * @return Response HTTP response
     */
    #[Route('/{id}/delete', name: 'tag_delete', requirements: ['id' => '[1-9]\d*'], methods: 'GET|DELETE')]
    public function delete(Request $request, Tag $tag): Response
    {
        $form = $this->createForm(
            FormType::class,
            $tag,
            [
                'method' => 'DELETE',
                'action' => $this->generateUrl('tag_delete', ['id' => $tag->getId()]),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->tagService->delete($tag);

            $this->addFlash(
                'success',
                $this->translator->trans('message.deleted_successfully')
            );

            return $this->redirectToRoute('tag_index');
        }

        return $this->render(
            'tag/delete.html.twig',
            [
                'form' => $form->createView(),
                'tag' => $tag,
            ]
        );
    }
}

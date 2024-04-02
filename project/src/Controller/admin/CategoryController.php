<?php

namespace App\Controller\admin;

use App\Entity\Category\Category;
use App\Form\CategoryFormType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

#[Route('/admin/category', name: 'admin_categories_')]
class CategoryController extends AbstractController
{

    public EntityManagerInterface $entityManager;
    private CategoryRepository $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository,EntityManagerInterface $entityManager)
    {
        $this->categoryRepository = $categoryRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $category = $this->categoryRepository->findBy([], ['id' => 'asc']);

        // pour chaque cat de la db
        foreach ($category as $cat){
            // je check si il a un parent
            if($cat->getIdParent() !== null){
                // si il a un parent je le recup via le repo et je le set dans la propriété non mappé en db ParentCategory
                // ca sera accessible comme sous objet en twig sous la forme category.parentCategory.laVarDeMaCat ( name , id etc )
                $catParent = $this->categoryRepository->find($cat->getIdParent());
                $cat->setParentCategory($catParent);

            }
        }

        return $this->render('/admin/category.html.twig', compact('category'));
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);
        $directory = $this->getParameter('kernel.project_dir') . '/public/img';
        if (!file_exists($directory)) {
            if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
            }
        }
        if ($form->isSubmitted() && $form->isValid()) {
            if ($category->getAttachment() !== null) {
                $file = $category->getAttachment();
                $extension = $file->getClientOriginalExtension();
                $image =  random_int(1, 99999) . '.' . $extension;
                try {
                    $file->move($directory, $image);
                    $category->setImage($image);
                    $this->entityManager->persist($category);
                } catch (RandomException $e) {

                }
            }
            if($form->get('parentCategory')->getData() !==null){
                $parentId = $form->get('parentCategory')->getData()->getId();
                $category->setIdParent($parentId);
            }

            $this->entityManager->persist($category);
            $this->entityManager->flush();

            $this->addFlash('success', 'Category created successfully.');

            return $this->redirectToRoute('admin_categories_new');
        }

        return $this->render('admin/category_new.html.twig', [
            'CategoryForm' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'edit')]
    #[ParamConverter('category',Category::class)]
    public function edit(Request $request, Category $category): Response
    {
//        $category = $this->categoryRepository->find($id);
        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);
        $directory = $this->getParameter('kernel.project_dir') . '/public/img';
        if (!file_exists($directory)) {
            if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
            }
        }
        if ($form->isSubmitted() && $form->isValid()) {
            if ($category->getAttachment() !== null) {
                $file = $category->getAttachment();
                $extension = $file->getClientOriginalExtension();
                $image =  random_int(1, 99999) . '.' . $extension;
                try {
                    $file->move($directory, $image);
                    $category->setImage($image);
                    $this->entityManager->persist($category);
                } catch (RandomException $e) {

                }
            }
            if($form->get('parentCategory')->getData() !==null){
                $parentId = $form->get('parentCategory')->getData()->getId();
                $category->setIdParent($parentId);
            }
            $this->entityManager->persist($category);
            $this->entityManager->flush();

            $this->addFlash('success', 'Category created successfully.');

            return $this->redirectToRoute('admin_categories_index');
        }

        return $this->render('admin/category_edit.html.twig', [
            'CategoryForm' => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'delete')]
    #[ParamConverter('category',Category::class)]
    public function delete(Category $category): Response
    {
        if($category->getImage()!==null){
            $this->removePicturesFile($category->getImage());
        }

        $this->entityManager->remove($category);
        $this->entityManager->flush();

        $this->addFlash('success', 'Category deleted successfully.');

        return $this->redirectToRoute('admin_categories_index');
    }

    private function removePicturesFile(string $fileName): void
    {

        $directory = $this->getParameter('kernel.project_dir') . '/public/img';

        // Check if the file exists before attempting to remove it
        $filePath = $directory . '/' . $fileName;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

}
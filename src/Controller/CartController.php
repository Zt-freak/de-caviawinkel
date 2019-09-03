<?php
namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Route("/cart")
 */
class CartController extends AbstractController
{
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @Route("/", name="cart_index", methods={"GET"})
     */
    public function index(ProductRepository $productRepository): Response
    {
        $cart = $this->session->get('cart');
        $products = [];

        foreach ($cart as &$value) {
            $product = $productRepository->findById($value[0])[0];
            array_push($products, ["id"=>$product->getId(), "name"=>$product->getName(), "price"=>$product->getPrice()]);
        }

        return $this->render('cart/index.html.twig', [
            'products' => $products,
        ]);
    }
}
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

use App\Form\CheckoutForm;

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

    private function add($id) {
        if ($this->session->get('cart') == null) {
            $cart = [];
            $cart[0][0] = $id;
            $cart[0][1] = 0;
        }
        else {
            $cart = $this->session->get('cart');
        }

        $exists = false;
        foreach ($cart as &$value) {
            if ($value[0] == $id) {
                $value[1]++;
                $exists = true;
            }
        }

        if ($exists == false) {
            array_push($cart, [$id, 1]);
        }
        unset($value);

        $this->session->set('cart', $cart);
    }

    private function remove($id) {
        if ($this->session->get('cart') == null) {
            $cart = [];
            $cart[0][0] = $id;
            $cart[0][1] = 0;
        }
        else {
            $cart = $this->session->get('cart');
        }

        foreach ($cart as &$value) {
            if ($value[0] == $id) {
                $value[1]--;

                if($value[1] <= 0 || $value[1] == null) {
                    unset($cart[array_search($value, $cart)]);
                }
            }
        }
        unset($value);

        $this->session->set('cart', $cart);
    }

    private function delete($id) {
        $cart = $this->session->get('cart');

        foreach ($cart as &$value) {
            if ($value[0] == $id) {
                unset($cart[array_search($value, $cart)]);
            }
        }
        unset($value);

        $this->session->set('cart', $cart);
    }

    /**
     * @Route("/", name="cart_index", methods={"GET"})
     */
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $add = $request->query->get("add");
        $remove = $request->query->get("remove");
        $delete = $request->query->get("delete");

        if ($add == "" && $remove == "" && $delete == "") {
            $isValid = true;
        }
        else {
            $isValid = false;
        }

        if ($productRepository->findById($add) != null) {
            if ($add !== null) {
                if ($productRepository->findById($add)) {
                    $this->add($add);
                    $isValid = true;
                }
            }
        }
        if ($productRepository->findById($remove) != null) {
            if ($remove !== null) {
                if ($productRepository->findById($remove)) {
                    $this->remove($remove);
                    $isValid = true;
                }
            }
        }
        if ($productRepository->findById($delete) != null) {
            if ($delete !== null) {
                if ($productRepository->findById($delete)) {
                    $this->delete($delete);
                    $isValid = true;
                }
            }
        }

        if ($isValid == false) {
            echo('<div class="errornotice"><img style="height: 100px;" src="https://pbs.twimg.com/media/D7FOGefXsAI_6td?format=jpg&name=4096x4096">Exception! Your entered number is an invalid value. Check your request in the address bar.<br />Also, trans rights are human rights!</div>');
        }
        
        $cart = $this->session->get('cart', []);
        $products = [];

        foreach ($cart as &$value) {
            $product = $productRepository->findById($value[0])[0];
            array_push($products, ["id"=>$product->getId(), "name"=>$product->getName(), "price"=>$product->getPrice()]);
        }

        return $this->render('cart/index.html.twig', [
            'products' => $products,
        ]);
    }

    /**
     * @Route("/checkout", name="cart_checkout", methods={"GET", "POST"})
     */
    public function checkout(Request $request, \Swift_Mailer $mailer, ProductRepository $productRepository): Response
    {
        $cart = $this->session->get('cart');
        $products = [];

        foreach ($cart as &$value) {
            $product = $productRepository->findById($value[0])[0];
            array_push($products, ["id"=>$product->getId(), "name"=>$product->getName(), "price"=>$product->getPrice()]);
        }

        $form = $this->createForm(CheckoutForm::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $checkout = $form->getData();

            $message = (new \Swift_Message('Hello Email'))
                ->setFrom('1037390@mborijnland.nl')
                ->setTo($checkout["e-mail"])
                ->setBody(
                    $this->renderView(
                        'cart/checkout_success.html.twig', [
                            'products' => $products,
                            'checkoutData' => $checkout
                        ]
                    ),
                    'text/html'
                )
            ;

            $mailer->send($message);
    
            return $this->render('cart/checkout_success.html.twig', [
                'products' => $products,
                'checkoutData' => $checkout
            ]);
        }

        return $this->render('cart/checkout.html.twig', [
            'products' => $products,
            'form' => $form->createView(),
        ]);
    }
}
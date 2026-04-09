import turtle

myPen = turtle.Turtle()
myPen.shape("turtle")
myPen.speed(10)
window = turtle.Screen()
window.bgcolor("#DDDDDD")

myPen.penup()
myPen.goto(-60,100)
myPen.fillcolor("#880088")
myPen.begin_fill()
myPen.goto(60,100)
myPen.goto(60,0)
myPen.goto(-60,-20)
myPen.goto(-60,100)
myPen.end_fill()

myPen.color("#FFFFFF")
myPen.goto(-54, 25)
myPen.write("101", None, None, font=("Arial",48, "normal"))

myPen.color("#222222")
myPen.goto(-120, -60)
myPen.write("101computing.net", None, None, font=("Arial",24, "normal"))

myPen.color("#777777")
myPen.goto(-116, -90)
myPen.write("Boost your programming skills", None, None, font=("Arial",14, "normal"))

myPen.hideturtle()
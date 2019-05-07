#!/usr/bin/env python3
#############################
# ISJ practice No. 5        #
# Tomáš Zálešák, xzales13   #
# 29.3.2018                 #
#############################

class Polynomial:
    """Polynomial class"""

    def __init__(self, *args, **kwargs):
        """Initialization to list"""

        self.values = []
        self.args = args
        self.kwargs = kwargs

        arg_list = not len(args) == 0 and isinstance(args[0], list)

        if arg_list: self.values = args[0]

        elif not self.kwargs:
            for arg in self.args: self.values.append(arg)

        else:
            values = {i.replace('x', ''): kwargs[i] for i in kwargs.keys()}
            values = {int(key): int(value) for key, value in values.items()}
            mk = max(values.keys())

            for i in range(mk + 1):
                if not i in values.keys():
                    values[i] = 0

            for i in range(mk + 1):
                self.values.append(values[i])

            while not len(self.values) == 1 and self.values[-1] == 0:
                self.values.pop()

    def __str__(self):
        """Polynomial to string"""
        output_str = ""

        z = True
        for v in self.values:
            if not v == 0:
                z = False
        if z == True:
            return "0"

        for i, value in reversed(list(enumerate(self.values))):
            if i == 0:
                if output_str:
                    if value > 0:
                        output_str = output_str + " + " + str(value)
                    elif value < 0:
                        output_str = output_str + " - " + str(-value)
                else:
                    if value > 0:
                        output_str = str(value)
                    elif value < 0:
                        output_str = "- " + str(-value)
            elif i == 1:
                    if output_str:
                        if value == 1:
                            output_str = output_str + " + x"
                        elif value == -1:
                            output_str = output_str + " - x"
                        elif value > 0:
                            output_str = output_str + " + " + str(value) + "x"
                        elif value < 0:
                            output_str = output_str + " - " + str(-value) + "x"
                    else:
                        if value == 1:
                            output_str = "x"
                        elif value == -1:
                            output_str = "- x"
                        elif value > 0:
                            output_str = str(value) + "x"
                        elif value < 0:
                            output_str = "- " + str(-value) + "x"
            elif output_str:
                if value == 1:
                    output_str = output_str + " + x^" + str(i)
                elif value == -1:
                    output_str = output_str + " - x^" + str(i)
                elif value > 0:
                    output_str = output_str + " + " + str(value) + "x^" + str(i)
                elif value < 0:
                    output_str = output_str + " - " + str(-value) + "x^" + str(i)
            else:
                if value == 1:
                    output_str = "x^" + str(i)
                elif value == -1:
                    output_str = "- x^" + str(i)
                elif value > 0:
                    output_str = str(value) + "x^" + str(i)
                elif value < 0:
                    output_str = "- " + str(-value) + "x^" + str(i)

        return output_str

    def __eq__(self, self2):
        """equation"""
        return self.values == self2.values

    def __add__(self, self2):
        """Add two polynomials"""

        if not len(self.values) == len(self2.values):
            if len(self.values) > len(self2.values):
                for i in range(len(self2.values), len(self.values)):
                    self2.values.append(int(0))
            else:
                for i in range(len(self.values), len(self2.values)):
                    self.values.append(int(0))
        r = Polynomial(list(x + y for x, y in zip(self.values, self2.values)))

        return r

    def derivative(self):
        """Derivative of polynomial"""
        values = []
        for i in range(1, len(self.values), 1):
            values.append(i * self.values[i])
        r = Polynomial(values)

        return r

    def multiply(x, y):
        """Multiply"""
        lx = len(x)
        ly = len(y)
        a = (lx + ly - 1)*[0]

        for i in range(lx):
            xi = x[i]
            for j in range(ly):
                a[i + j] += xi * y[j]
        return a

    def at_value(self, *args):
        """More arguments"""
        number = 0.0

        if not len(args) == 1:
            number1 = 0.0
            number2 = 0.0
            my_x1 = float(args[0])
            my_x2 = float(args[1])

            for i in range(len(self.values)):
                number1 = number1 + (my_x1 ** i) * self.values[i]
            for i in range(len(self.values)):
                number2 = number2 + self.values[i] * (my_x2 ** i)

            number = number2 - number1
        else:
            my_x = float(args[0])
            for i in range(len(self.values)):
                number = number + self.values[i] * (my_x ** i)

        return number

    def __pow__(self, n):
        """Power from n+"""
        p = [1]
        for i in range(n):
            p = Polynomial.multiply(p, self.values)
        return Polynomial(p)

def test():
    assert str(Polynomial(0,1,0,-1,4,-2,0,1,3,0)) == "3x^8 + x^7 - 2x^5 + 4x^4 - x^3 + x"
    assert str(Polynomial([-5,1,0,-1,4,-2,0,1,3,0])) == "3x^8 + x^7 - 2x^5 + 4x^4 - x^3 + x - 5"
    assert str(Polynomial(x7=1, x4=4, x8=3, x9=0, x0=0, x5=-2, x3= -1, x1=1)) == "3x^8 + x^7 - 2x^5 + 4x^4 - x^3 + x"
    assert str(Polynomial(x2=0)) == "0"
    assert str(Polynomial(x0=0)) == "0"
    assert Polynomial(x0=2, x1=0, x3=0, x2=3) == Polynomial(2,0,3)
    assert Polynomial(x2=0) == Polynomial(x0=0)
    assert str(Polynomial(x0=1)+Polynomial(x1=1)) == "x + 1"
    assert str(Polynomial([-1,1,1,0])+Polynomial(1,-1,1)) == "2x^2"
    pol1 = Polynomial(x2=3, x0=1)
    pol2 = Polynomial(x1=1, x3=0)
    assert str(pol1+pol2) == "3x^2 + x + 1"
    assert str(pol1+pol2) == "3x^2 + x + 1"
    assert str(Polynomial(x0=-1,x1=1)**1) == "x - 1"
    assert str(Polynomial(x0=-1,x1=1)**2) == "x^2 - 2x + 1"
    pol3 = Polynomial(x0=-1,x1=1)
    assert str(pol3**4) == "x^4 - 4x^3 + 6x^2 - 4x + 1"
    assert str(pol3**4) == "x^4 - 4x^3 + 6x^2 - 4x + 1"
    assert str(Polynomial(x0=2).derivative()) == "0"
    assert str(Polynomial(x3=2,x1=3,x0=2).derivative()) == "6x^2 + 3"
    assert str(Polynomial(x3=2,x1=3,x0=2).derivative().derivative()) == "12x"
    pol4 = Polynomial(x3=2,x1=3,x0=2)
    assert str(pol4.derivative()) == "6x^2 + 3"
    assert str(pol4.derivative()) == "6x^2 + 3"
    assert Polynomial(-2,3,4,-5).at_value(0) == -2
    assert Polynomial(x2=3, x0=-1, x1=-2).at_value(3) == 20
    assert Polynomial(x2=3, x0=-1, x1=-2).at_value(3,5) == 44
    pol5 = Polynomial([1,0,-2])
    assert pol5.at_value(-2.4) == -10.52
    assert pol5.at_value(-2.4) == -10.52
    assert pol5.at_value(-1,3.6) == -23.92
    assert pol5.at_value(-1,3.6) == -23.92

if __name__ == '__main__':
    test()
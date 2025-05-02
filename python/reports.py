import sys
import json
import pandas as pd
from fpdf import FPDF

data = json.loads(sys.argv[1])

pdf = FPDF()
pdf.add_page()
pdf.set_font("Arial", size=12)
pdf.cell(200, 10, txt=f"Report: {data['start_date']} to {data['end_date']}", ln=1)
pdf.output("report.pdf")

with open("report.pdf", "rb") as f:
    print(f.read())